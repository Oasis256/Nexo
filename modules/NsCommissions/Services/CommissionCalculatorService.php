<?php

namespace Modules\NsCommissions\Services;

use App\Classes\Currency;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\NsCommissions\Events\CommissionAfterCreatedEvent;
use Modules\NsCommissions\Models\Commission;
use Modules\NsCommissions\Models\EarnedCommission;
use Modules\NsCommissions\Models\OrderProductCommissionAssignment;

class CommissionCalculatorService
{
    /**
     * Calculate and record commissions for an order
     */
    public function processOrderCommissions(Order $order): Collection
    {
        $earnedCommissions = collect();

        foreach ($order->products as $orderProduct) {
            $earned = $this->processProductCommission($order, $orderProduct);
            if ($earned) {
                $earnedCommissions->push($earned);
            }
        }

        return $earnedCommissions;
    }

    /**
     * Process commission for a single order product
     */
    public function processProductCommission(Order $order, OrderProduct $orderProduct): ?EarnedCommission
    {
        // Get the assigned user for this product (POS selection)
        $assignment = OrderProductCommissionAssignment::where('order_product_id', $orderProduct->id)->first();

        // If no assignment, fall back to order author
        $userId = $assignment?->user_id ?? $order->author;
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        // Get applicable commission for this user's role and product
        $commission = $this->findApplicableCommission($user, $orderProduct);

        if (!$commission) {
            return null;
        }

        // Calculate commission value based on type
        $commissionValue = $this->calculateCommissionValue($commission, $orderProduct);

        if ($commissionValue <= 0) {
            return null;
        }

        // Record earned commission
        return $this->recordEarnedCommission(
            commission: $commission,
            order: $order,
            orderProduct: $orderProduct,
            userId: $userId,
            value: $commissionValue
        );
    }

    /**
     * Find applicable commission for user and product
     */
    public function findApplicableCommission(User $user, OrderProduct $orderProduct): ?Commission
    {
        $roleIds = $user->roles->pluck('id')->toArray();

        if (empty($roleIds)) {
            return null;
        }

        return Commission::active()
            ->whereIn('role_id', $roleIds)
            ->where(function ($query) use ($orderProduct) {
                // Commission applies to product's category
                $query->whereHas('categories', function ($q) use ($orderProduct) {
                    $q->where('category_id', $orderProduct->product_category_id);
                })
                // Or has no category restrictions (applies to all)
                ->orWhereDoesntHave('categories');
            })
            // Priority: on_the_house > fixed > percentage
            ->orderByRaw("FIELD(type, 'on_the_house', 'fixed', 'percentage')")
            ->first();
    }

    /**
     * Calculate commission value based on type
     */
    public function calculateCommissionValue(Commission $commission, OrderProduct $orderProduct): float
    {
        $quantity = (float) $orderProduct->quantity;

        switch ($commission->type) {
            case Commission::TYPE_ON_THE_HOUSE:
                // Fixed value regardless of price - multiplied by quantity
                return Currency::define($commission->value)
                    ->multipliedBy($quantity)
                    ->getRaw();

            case Commission::TYPE_FIXED:
                // Check for product-specific value first
                $productValue = $commission->getProductValue($orderProduct->product_id);
                $value = $productValue ?? $commission->value;

                return Currency::define($value)
                    ->multipliedBy($quantity)
                    ->getRaw();

            case Commission::TYPE_PERCENTAGE:
                $baseAmount = $this->getBaseAmount($commission, $orderProduct);

                return Currency::define($baseAmount)
                    ->multipliedBy($commission->value)
                    ->dividedBy(100)
                    ->multipliedBy($quantity)
                    ->getRaw();

            default:
                return 0;
        }
    }

    /**
     * Get base amount for percentage calculation
     */
    protected function getBaseAmount(Commission $commission, OrderProduct $orderProduct): float
    {
        return match ($commission->calculation_base ?? Commission::BASE_GROSS) {
            Commission::BASE_GROSS => (float) $orderProduct->unit_price + (float) $orderProduct->discount,
            Commission::BASE_NET => (float) $orderProduct->unit_price,
            Commission::BASE_FIXED => (float) $orderProduct->unit_price,
            default => (float) $orderProduct->unit_price,
        };
    }

    /**
     * Record earned commission
     */
    protected function recordEarnedCommission(
        Commission $commission,
        Order $order,
        OrderProduct $orderProduct,
        int $userId,
        float $value
    ): EarnedCommission {
        $earned = new EarnedCommission();
        $earned->name = $commission->name;
        $earned->value = $value;
        $earned->user_id = $userId;
        $earned->order_id = $order->id;
        $earned->order_product_id = $orderProduct->id;
        $earned->product_id = $orderProduct->product_id;
        $earned->quantity = $orderProduct->quantity;
        $earned->commission_id = $commission->id;
        $earned->commission_type = $commission->type;
        $earned->base_amount = $orderProduct->unit_price;
        $earned->author = Auth::id() ?? $order->author;
        $earned->created_at = $order->created_at;
        $earned->save();

        // Dispatch event
        CommissionAfterCreatedEvent::dispatch($earned);

        return $earned;
    }

    /**
     * Delete all commissions for an order
     */
    public function deleteOrderCommissions(Order $order): int
    {
        return EarnedCommission::where('order_id', $order->id)->delete();
    }

    /**
     * Assign user to earn commission for specific order product
     * Called from POS during transaction
     */
    public function assignCommissionUser(
        int $orderId,
        int $orderProductId,
        int $userId,
        ?int $commissionId = null
    ): OrderProductCommissionAssignment {
        return OrderProductCommissionAssignment::updateOrCreate(
            ['order_product_id' => $orderProductId],
            [
                'order_id' => $orderId,
                'user_id' => $userId,
                'commission_id' => $commissionId,
            ]
        );
    }

    /**
     * Get eligible users who can earn commission for a product
     * Based on roles that have commission configurations
     */
    public function getEligibleCommissionUsers(int $productCategoryId): Collection
    {
        // Get role IDs that have active commissions for this category
        $roleIds = Commission::active()
            ->where(function ($query) use ($productCategoryId) {
                $query->whereHas('categories', function ($q) use ($productCategoryId) {
                    $q->where('category_id', $productCategoryId);
                })
                ->orWhereDoesntHave('categories');
            })
            ->pluck('role_id')
            ->unique();

        // Get users with these roles
        return User::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('nexopos_roles.id', $roleIds);
        })->get(['id', 'username', 'email']);
    }

    /**
     * Preview commission calculation for a cart item
     * Used in POS to show expected commission before checkout
     */
    public function previewCommission(
        int $productId,
        int $productCategoryId,
        float $unitPrice,
        float $quantity,
        int $userId
    ): array {
        $user = User::find($userId);
        if (!$user) {
            return ['value' => 0, 'commission' => null];
        }

        // Create a mock OrderProduct for calculation
        $mockProduct = new OrderProduct();
        $mockProduct->product_id = $productId;
        $mockProduct->product_category_id = $productCategoryId;
        $mockProduct->unit_price = $unitPrice;
        $mockProduct->quantity = $quantity;
        $mockProduct->discount = 0;

        $commission = $this->findApplicableCommission($user, $mockProduct);

        if (!$commission) {
            return ['value' => 0, 'commission' => null];
        }

        $value = $this->calculateCommissionValue($commission, $mockProduct);

        return [
            'value' => $value,
            'formatted_value' => ns()->currency->define($value)->format(),
            'commission' => [
                'id' => $commission->id,
                'name' => $commission->name,
                'type' => $commission->type,
            ],
        ];
    }
}
