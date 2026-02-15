<?php

namespace Modules\RenCommissions\Services;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Options;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Models\PosCommissionSession;

/**
 * Per-Item Commission Service
 * 
 * Core service for managing commission assignments in POS.
 * Handles calculation, session management, and order conversion.
 */
class PerItemCommissionService
{
    /**
     * Options service instance
     */
    protected Options $options;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->options = app()->make(Options::class);
    }

    /**
     * Get eligible staff members who can earn commissions
     *
     * @return Collection
     */
    public function getEligibleEarners(): Collection
    {
        $allowedRoles = $this->options->get('rencommissions_eligible_roles', []);
        
        if (empty($allowedRoles)) {
            // Default to all active users if no roles configured
            return User::where('active', true)
                ->select(['id', 'username', 'email'])
                ->orderBy('username')
                ->get();
        }

        return User::where('active', true)
            ->whereHas('roles', function ($query) use ($allowedRoles) {
                $query->whereIn('namespace', $allowedRoles);
            })
            ->select(['id', 'username', 'email'])
            ->orderBy('username')
            ->get();
    }

    /**
     * Get active commission types
     *
     * @return Collection
     */
    public function getCommissionTypes(): Collection
    {
        return CommissionType::active()
            ->ordered()
            ->get();
    }

    /**
     * Get commission type by ID or identifier
     *
     * @param int|string $identifier
     * @return CommissionType|null
     */
    public function getCommissionType($identifier): ?CommissionType
    {
        if (is_numeric($identifier)) {
            return CommissionType::find($identifier);
        }

        return CommissionType::where('calculation_method', $identifier)->first();
    }

    /**
     * Calculate commission for a product
     *
     * @param string $commissionType
     * @param Product|int $product
     * @param float $unitPrice
     * @param float $quantity
     * @param float|null $customValue
     * @return array
     */
    public function calculateCommission(
        string $commissionType,
        $product,
        float $unitPrice,
        float $quantity = 1,
        ?float $customValue = null
    ): array {
        if (is_int($product)) {
            $product = Product::find($product);
        }

        if (!$product) {
            throw new Exception(__('Product not found'));
        }

        $totalPrice = $unitPrice * $quantity;
        $commissionPerUnit = 0;
        $rate = $customValue;
        $calculationDetails = [];

        switch ($commissionType) {
            case CommissionType::METHOD_PERCENTAGE:
                $rate = $customValue ?? $this->options->get('rencommissions_default_percentage', 5);
                $commissionPerUnit = ($unitPrice * $rate) / 100;
                $calculationDetails = [
                    'method' => 'percentage',
                    'rate' => $rate,
                    'unit_price' => $unitPrice,
                    'formula' => "($unitPrice × $rate%) = $commissionPerUnit",
                ];
                break;

            case CommissionType::METHOD_FIXED:
                $rate = $product->commission_value ?? 0;
                $commissionPerUnit = $rate;
                $calculationDetails = [
                    'method' => 'fixed',
                    'product_commission_value' => $rate,
                    'formula' => "fixed amount from product = $commissionPerUnit",
                ];
                break;

            case CommissionType::METHOD_ON_THE_HOUSE:
                $rate = $this->options->get('rencommissions_on_the_house_amount', 1);
                $commissionPerUnit = $rate;
                $calculationDetails = [
                    'method' => 'on_the_house',
                    'settings_amount' => $rate,
                    'formula' => "settings amount per unit = $commissionPerUnit",
                ];
                break;

            default:
                throw new Exception(__('Invalid commission type'));
        }

        $totalCommission = $commissionPerUnit * $quantity;

        return [
            'commission_type' => $commissionType,
            'rate' => $rate,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'commission_per_unit' => round($commissionPerUnit, 2),
            'total_commission' => round($totalCommission, 2),
            'calculation_details' => $calculationDetails,
        ];
    }

    /**
     * Preview commission calculation (without saving)
     *
     * @param array $data
     * @return array
     */
    public function previewCommission(array $data): array
    {
        return $this->calculateCommission(
            $data['commission_type'],
            $data['product_id'],
            $data['unit_price'],
            $data['quantity'] ?? 1,
            $data['commission_value'] ?? null
        );
    }

    /**
     * Assign commission to a product in POS session
     *
     * @param array $data
     * @return PosCommissionSession
     */
    public function assignPosCommission(array $data): PosCommissionSession
    {
        $sessionId = request()->session()->getId();
        $product = Product::findOrFail($data['product_id']);
        
        // Calculate commission
        $calculation = $this->calculateCommission(
            $data['commission_type'],
            $product,
            $data['unit_price'],
            $data['quantity'] ?? 1,
            $data['commission_value'] ?? null
        );

        // Check for existing commission on same product (keyed by product_id)
        $existing = PosCommissionSession::getForProduct($sessionId, $data['product_id']);
        
        if ($existing) {
            // Update existing record
            $existing->fill([
                'staff_id' => $data['staff_id'],
                'commission_type' => $data['commission_type'],
                'commission_value' => $calculation['rate'],
                'commission_amount' => $calculation['commission_per_unit'],
                'unit_price' => $calculation['unit_price'],
                'quantity' => $calculation['quantity'],
                'total_price' => $calculation['total_price'],
                'total_commission' => $calculation['total_commission'],
                'assigned_by' => Auth::id(),
            ]);
            $existing->save();
            return $existing;
        }

        // Create new session record
        return PosCommissionSession::create([
            'session_id' => $sessionId,
            'product_index' => $data['product_index'],
            'product_id' => $data['product_id'],
            'staff_id' => $data['staff_id'],
            'commission_type' => $data['commission_type'],
            'commission_value' => $calculation['rate'],
            'commission_amount' => $calculation['commission_per_unit'],
            'unit_price' => $calculation['unit_price'],
            'quantity' => $calculation['quantity'],
            'total_price' => $calculation['total_price'],
            'total_commission' => $calculation['total_commission'],
            'assigned_by' => Auth::id(),
        ]);
    }

    /**
     * Get all session commissions for current session
     *
     * @param string|null $sessionId
     * @return Collection
     */
    public function getSessionCommissions(?string $sessionId = null): Collection
    {
        $sessionId = $sessionId ?? request()->session()->getId();
        return PosCommissionSession::getBySessionId($sessionId);
    }

    /**
     * Get session totals
     *
     * @param string|null $sessionId
     * @return array
     */
    public function getSessionTotals(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?? request()->session()->getId();
        return PosCommissionSession::getSessionTotals($sessionId);
    }

    /**
     * Clear all commissions for a session
     *
     * @param string|null $sessionId
     * @return int Number of records deleted
     */
    public function clearSession(?string $sessionId = null): int
    {
        $sessionId = $sessionId ?? request()->session()->getId();
        return PosCommissionSession::clearSession($sessionId);
    }

    /**
     * Remove commission for a specific product index
     *
     * @param int $productIndex
     * @param string|null $sessionId
     * @return bool
     */
    public function removeCommission(int $productIndex, ?string $sessionId = null): bool
    {
        $sessionId = $sessionId ?? request()->session()->getId();
        return PosCommissionSession::removeByProductIndex($sessionId, $productIndex);
    }

    /**
     * Convert session commissions to permanent order records
     * Called when order is finalized
     *
     * @param int $orderId
     * @param array $orderProducts Array of order product data with mapping
     * @param string|null $sessionId
     * @return Collection Created OrderItemCommission records
     */
    public function convertSessionCommissions(int $orderId, array $orderProducts, ?string $sessionId = null): Collection
    {
        $sessionId = $sessionId ?? request()->session()->getId();
        $sessionCommissions = $this->getSessionCommissions($sessionId);
        $created = collect();

        foreach ($sessionCommissions as $sessionCommission) {
            // Find the matching order product by product_id
            $orderProduct = $this->findOrderProductByProductId($orderProducts, $sessionCommission->product_id);
            
            if (!$orderProduct) {
                Log::warning('RenCommissions: Could not find order product for session commission', [
                    'session_id' => $sessionId,
                    'product_id' => $sessionCommission->product_id,
                    'order_products_count' => count($orderProducts),
                ]);
                continue;
            }

            // Create permanent commission record
            $commission = OrderItemCommission::create([
                'order_id' => $orderId,
                'order_product_id' => $orderProduct['id'],
                'product_id' => $sessionCommission->product_id,
                'commission_type' => $sessionCommission->commission_type,
                'earner_id' => $sessionCommission->staff_id,
                'assigned_by' => $sessionCommission->assigned_by,
                'commission_rate' => $sessionCommission->commission_value,
                'unit_price' => $sessionCommission->unit_price,
                'quantity' => $sessionCommission->quantity,
                'commission_per_unit' => $sessionCommission->commission_amount,
                'total_commission' => $sessionCommission->total_commission,
                'calculation_details' => [
                    'type' => $sessionCommission->commission_type,
                    'session_id' => $sessionId,
                    'converted_at' => now()->toDateTimeString(),
                ],
                'status' => OrderItemCommission::STATUS_PENDING,
            ]);

            $created->push($commission);
        }

        // Clear the session after successful conversion
        $this->clearSession($sessionId);

        return $created;
    }

    /**
     * Find order product by cart index (legacy)
     *
     * @param array $orderProducts
     * @param int $index
     * @return array|null
     */
    protected function findOrderProductByIndex(array $orderProducts, int $index): ?array
    {
        foreach ($orderProducts as $i => $product) {
            if ($i === $index || ($product['index'] ?? $i) === $index) {
                return $product;
            }
        }
        return null;
    }

    /**
     * Find order product by product_id
     *
     * @param array $orderProducts
     * @param int $productId
     * @return array|null
     */
    protected function findOrderProductByProductId(array $orderProducts, int $productId): ?array
    {
        foreach ($orderProducts as $product) {
            if (($product['product_id'] ?? 0) === $productId) {
                return $product;
            }
        }
        return null;
    }

    /**
     * Get commissions for an order
     *
     * @param int $orderId
     * @return Collection
     */
    public function getOrderCommissions(int $orderId): Collection
    {
        return OrderItemCommission::byOrder($orderId)
            ->with(['earner', 'product', 'orderProduct'])
            ->get();
    }

    /**
     * Get commissions summary for an earner
     *
     * @param int $earnerId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getEarnerSummary(int $earnerId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = OrderItemCommission::byEarner($earnerId);
        
        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $commissions = $query->get();

        return [
            'earner_id' => $earnerId,
            'total_count' => $commissions->count(),
            'total_amount' => $commissions->sum('total_commission'),
            'pending_amount' => $commissions->where('status', OrderItemCommission::STATUS_PENDING)->sum('total_commission'),
            'paid_amount' => $commissions->where('status', OrderItemCommission::STATUS_PAID)->sum('total_commission'),
            'voided_amount' => $commissions->where('status', OrderItemCommission::STATUS_VOIDED)->sum('total_commission'),
        ];
    }

    /**
     * Update commission status
     *
     * @param int $commissionId
     * @param string $status
     * @param array $additionalData
     * @return OrderItemCommission
     */
    public function updateCommissionStatus(int $commissionId, string $status, array $additionalData = []): OrderItemCommission
    {
        $commission = OrderItemCommission::findOrFail($commissionId);

        switch ($status) {
            case OrderItemCommission::STATUS_PAID:
                $commission->markAsPaid(
                    Auth::id(),
                    $additionalData['payment_reference'] ?? null
                );
                break;

            case OrderItemCommission::STATUS_VOIDED:
                $commission->void(
                    Auth::id(),
                    $additionalData['reason'] ?? 'No reason provided'
                );
                break;

            case OrderItemCommission::STATUS_CANCELLED:
                $commission->cancel();
                break;

            default:
                $commission->status = $status;
                $commission->save();
        }

        return $commission->fresh();
    }

    /**
     * Void commission with reason
     *
     * @param int $commissionId
     * @param string $reason
     * @return OrderItemCommission
     */
    public function voidCommission(int $commissionId, string $reason): OrderItemCommission
    {
        return $this->updateCommissionStatus($commissionId, OrderItemCommission::STATUS_VOIDED, [
            'reason' => $reason,
        ]);
    }

    /**
     * Void all commissions for an order (e.g., when order is voided)
     *
     * @param int $orderId
     * @param string $reason
     * @return int Number of voided commissions
     */
    public function voidOrderCommissions(int $orderId, string $reason): int
    {
        $commissions = OrderItemCommission::byOrder($orderId)
            ->pending()
            ->get();

        $voided = 0;
        foreach ($commissions as $commission) {
            if ($commission->void(Auth::id(), $reason)) {
                $voided++;
            }
        }

        return $voided;
    }
}
