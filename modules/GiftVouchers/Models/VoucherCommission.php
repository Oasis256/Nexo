<?php
/**
 * Voucher Commission Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderProduct;
use Modules\GiftVouchers\Enums\CommissionType;

class VoucherCommission extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_voucher_commissions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'redemption_item_id',
        'voucher_id',
        'order_id',
        'order_product_id',
        'product_id',
        'user_id',
        'base_amount',
        'commission_rate',
        'commission_type',
        'value',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'base_amount' => 'decimal:5',
        'commission_rate' => 'decimal:5',
        'value' => 'decimal:5',
    ];

    /**
     * Get the redemption item that generated this commission.
     */
    public function redemptionItem(): BelongsTo
    {
        return $this->belongsTo(VoucherRedemptionItem::class, 'redemption_item_id');
    }

    /**
     * Get the voucher.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the order product.
     */
    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the user earning the commission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the author (who created this record).
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    /**
     * Check if commission is percentage-based.
     */
    public function isPercentage(): bool
    {
        return $this->commission_type === CommissionType::PERCENTAGE->value;
    }

    /**
     * Check if commission is fixed amount.
     */
    public function isFixed(): bool
    {
        return $this->commission_type === CommissionType::FIXED->value;
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by voucher.
     */
    public function scopeForVoucher($query, int $voucherId)
    {
        return $query->where('voucher_id', $voucherId);
    }

    /**
     * Scope to filter by order.
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
