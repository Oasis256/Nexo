<?php
/**
 * Voucher Template Item Model
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;
use App\Models\Unit;
use Modules\GiftVouchers\Enums\CommissionType;

class VoucherTemplateItem extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'nexopos_gift_voucher_template_items';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'template_id',
        'product_id',
        'unit_id',
        'quantity',
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

        static::saving(function ($item) {
            // Auto-calculate total price
            $item->total_price = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            // Recalculate template total
            $item->template?->recalculateTotalValue();
        });

        static::deleted(function ($item) {
            // Recalculate template total
            $item->template?->recalculateTotalValue();
        });
    }

    /**
     * Get the parent template.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(VoucherTemplate::class, 'template_id');
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
     * Calculate commission amount for a given base amount.
     */
    public function calculateCommission(float $baseAmount): float
    {
        if ($this->commission_type === CommissionType::PERCENTAGE->value) {
            return $baseAmount * ($this->commission_rate / 100);
        }

        return $this->commission_rate;
    }
}
