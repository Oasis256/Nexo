<?php
/**
 * Voucher Redemption Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Customer;
use App\Models\Order;

class VoucherRedemption extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_voucher_redemptions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'voucher_id',
        'redeemer_id',
        'redemption_order_id',
        'total_value',
        'revenue_transaction_id',
        'author',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'total_value' => 'decimal:5',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($redemption) {
            if (empty($redemption->uuid)) {
                $redemption->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the voucher being redeemed.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Get the redeemer (customer using the voucher).
     */
    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'redeemer_id');
    }

    /**
     * Get the redemption order.
     */
    public function redemptionOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'redemption_order_id');
    }

    /**
     * Get the redemption items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VoucherRedemptionItem::class, 'redemption_id');
    }

    /**
     * Get the author (cashier who processed).
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author');
    }

    /**
     * Get all commissions generated from this redemption.
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(VoucherCommission::class, 'redemption_item_id')
            ->through('items');
    }

    /**
     * Get the purchaser (via voucher) - for points attribution.
     */
    public function getPurchaserAttribute(): ?Customer
    {
        return $this->voucher?->purchaser;
    }
}
