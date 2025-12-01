<?php
/**
 * Voucher Redemption Item Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User;
use App\Models\OrderProduct;

class VoucherRedemptionItem extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_voucher_redemption_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'redemption_id',
        'voucher_item_id',
        'order_product_id',
        'quantity_redeemed',
        'value_redeemed',
        'service_provider_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity_redeemed' => 'decimal:5',
        'value_redeemed' => 'decimal:5',
    ];

    /**
     * Get the parent redemption.
     */
    public function redemption(): BelongsTo
    {
        return $this->belongsTo(VoucherRedemption::class, 'redemption_id');
    }

    /**
     * Get the voucher item being redeemed.
     */
    public function voucherItem(): BelongsTo
    {
        return $this->belongsTo(VoucherItem::class, 'voucher_item_id');
    }

    /**
     * Get the order product.
     */
    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id');
    }

    /**
     * Get the service provider (user earning commission).
     */
    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_provider_id');
    }

    /**
     * Get the commission generated from this redemption item.
     */
    public function commission(): HasOne
    {
        return $this->hasOne(VoucherCommission::class, 'redemption_item_id');
    }

    /**
     * Get the product (via voucher item).
     */
    public function getProductAttribute()
    {
        return $this->voucherItem?->product;
    }
}
