<?php

namespace Modules\RenCommissions\Models;

use App\Classes\Hook;
use App\Models\NsModel;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;

/**
 * Order Item Commission Model
 * 
 * Permanent commission records created when orders are finalized.
 * Converted from PosCommissionSession records on order completion.
 * Multistore-aware: table name is dynamically prefixed based on active store.
 *
 * @property int $id
 * @property int $order_id
 * @property int $order_product_id
 * @property int $product_id
 * @property string $commission_type
 * @property int $earner_id
 * @property int $assigned_by
 * @property float $commission_rate
 * @property float $unit_price
 * @property float $quantity
 * @property float $commission_per_unit
 * @property float $total_commission
 * @property array $calculation_details
 * @property string $status
 * @property int $voided_by
 * @property string $voided_at
 * @property string $void_reason
 * @property string $paid_at
 * @property int $paid_by
 * @property string $payment_reference
 * @property string $created_at
 * @property string $updated_at
 */
class OrderItemCommission extends NsModel
{
    /**
     * Base table name (without multistore prefix)
     */
    protected $table = 'rencommissions_order_item_commissions';

    /**
     * Fillable fields
     */
    protected $fillable = [
        'order_id',
        'order_product_id',
        'product_id',
        'commission_type',
        'earner_id',
        'assigned_by',
        'commission_rate',
        'unit_price',
        'quantity',
        'commission_per_unit',
        'total_commission',
        'calculation_details',
        'status',
        'voided_by',
        'voided_at',
        'void_reason',
        'paid_at',
        'paid_by',
        'payment_reference',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'order_id' => 'integer',
        'order_product_id' => 'integer',
        'product_id' => 'integer',
        'earner_id' => 'integer',
        'assigned_by' => 'integer',
        'commission_rate' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'commission_per_unit' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'calculation_details' => 'array',
        'voided_by' => 'integer',
        'voided_at' => 'datetime',
        'paid_at' => 'datetime',
        'paid_by' => 'integer',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_VOIDED = 'voided';

    /**
     * Constructor - table name filtered by NsModel for multistore support
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Relationship: Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Relationship: Order Product (line item)
     */
    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'id');
    }

    /**
     * Relationship: Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Relationship: Earner (staff member who earns commission)
     */
    public function earner()
    {
        return $this->belongsTo(User::class, 'earner_id', 'id');
    }

    /**
     * Relationship: Assigned by (user who assigned commission)
     */
    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /**
     * Relationship: Voided by (user who voided)
     */
    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by', 'id');
    }

    /**
     * Relationship: Paid by (user who processed payment)
     */
    public function paidByUser()
    {
        return $this->belongsTo(User::class, 'paid_by', 'id');
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending commissions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Paid commissions
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope: By earner
     */
    public function scopeByEarner($query, int $userId)
    {
        return $query->where('earner_id', $userId);
    }

    /**
     * Scope: By order
     */
    public function scopeByOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope: Date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if commission is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if commission is paid
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if commission is voided
     */
    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Mark commission as paid
     */
    public function markAsPaid(int $paidBy, ?string $reference = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_PAID;
        $this->paid_at = now();
        $this->paid_by = $paidBy;
        $this->payment_reference = $reference;
        
        return $this->save();
    }

    /**
     * Void the commission
     */
    public function void(int $voidedBy, string $reason): bool
    {
        if ($this->isPaid() || $this->isVoided()) {
            return false;
        }

        $this->status = self::STATUS_VOIDED;
        $this->voided_at = now();
        $this->voided_by = $voidedBy;
        $this->void_reason = $reason;
        
        return $this->save();
    }

    /**
     * Cancel the commission
     */
    public function cancel(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Get available status options
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_PAID => __('Paid'),
            self::STATUS_CANCELLED => __('Cancelled'),
            self::STATUS_VOIDED => __('Voided'),
        ];
    }

    /**
     * Get total commissions for an earner
     */
    public static function getTotalForEarner(int $earnerId, ?string $status = null): float
    {
        $query = static::byEarner($earnerId);
        
        if ($status) {
            $query->byStatus($status);
        }
        
        return $query->sum('total_commission');
    }

    /**
     * Get commissions summary for an order
     */
    public static function getOrderSummary(int $orderId): array
    {
        $commissions = static::byOrder($orderId)->get();
        
        return [
            'count' => $commissions->count(),
            'total' => $commissions->sum('total_commission'),
            'pending' => $commissions->where('status', self::STATUS_PENDING)->sum('total_commission'),
            'paid' => $commissions->where('status', self::STATUS_PAID)->sum('total_commission'),
        ];
    }
}
