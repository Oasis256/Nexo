<?php
/**
 * Voucher Item Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;
use App\Models\Unit;
use Modules\GiftVouchers\Enums\CommissionType;

class VoucherItem extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_voucher_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'voucher_id',
        'template_item_id',
        'product_id',
        'unit_id',
        'quantity',
        'quantity_remaining',
        'unit_price',
        'total_price',
        'commission_rate',
        'commission_type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'quantity' => 'decimal:5',
        'quantity_remaining' => 'decimal:5',
        'unit_price' => 'decimal:5',
        'total_price' => 'decimal:5',
        'commission_rate' => 'decimal:5',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($item) {
            // Auto-calculate total price
            $item->total_price = $item->quantity * $item->unit_price;
            
            // Set remaining quantity to full quantity on creation
            if (is_null($item->quantity_remaining)) {
                $item->quantity_remaining = $item->quantity;
            }
        });
    }

    /**
     * Get the parent voucher.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Get the template item this was created from.
     */
    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(VoucherTemplateItem::class, 'template_item_id');
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Get the redemption items for this voucher item.
     */
    public function redemptionItems(): HasMany
    {
        return $this->hasMany(VoucherRedemptionItem::class, 'voucher_item_id');
    }

    /**
     * Check if item is fully redeemed.
     */
    public function isFullyRedeemed(): bool
    {
        return $this->quantity_remaining <= 0;
    }

    /**
     * Check if item has remaining quantity.
     */
    public function hasRemaining(): bool
    {
        return $this->quantity_remaining > 0;
    }

    /**
     * Get the redeemed quantity.
     */
    public function getRedeemedQuantityAttribute(): float
    {
        return $this->quantity - $this->quantity_remaining;
    }

    /**
     * Get the remaining value for this item.
     */
    public function getRemainingValueAttribute(): float
    {
        return $this->quantity_remaining * $this->unit_price;
    }

    /**
     * Calculate commission amount for a given redeemed quantity.
     */
    public function calculateCommission(float $redeemedQuantity): float
    {
        $baseAmount = $redeemedQuantity * $this->unit_price;

        if ($this->commission_type === CommissionType::PERCENTAGE->value) {
            return $baseAmount * ($this->commission_rate / 100);
        }

        // Fixed commission per unit redeemed
        return $redeemedQuantity * $this->commission_rate;
    }

    /**
     * Scope to filter items with remaining quantity.
     */
    public function scopeWithRemaining($query)
    {
        return $query->where('quantity_remaining', '>', 0);
    }

    /**
     * Scope to filter by product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
