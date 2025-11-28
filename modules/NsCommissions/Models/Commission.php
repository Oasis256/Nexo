<?php

namespace Modules\NsCommissions\Models;

use App\Models\NsModel;

class Commission extends NsModel
{
    protected $table = 'nexopos_commissions';

    /**
     * Commission Types
     */
    const TYPE_ON_THE_HOUSE = 'on_the_house';
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';

    /**
     * Calculation Bases (for percentage type)
     */
    const BASE_FIXED = 'fixed';      // Ignore price variations (for on_the_house)
    const BASE_GROSS = 'gross';      // Calculate on price before discounts
    const BASE_NET = 'net';          // Calculate on final price after discounts

    protected $fillable = [
        'name',
        'active',
        'type',
        'calculation_base',
        'value',
        'role_id',
        'author',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
        'value' => 'decimal:5',
    ];

    /**
     * Get available commission types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ON_THE_HOUSE => __m('On The House', 'NsCommissions'),
            self::TYPE_FIXED => __m('Fixed', 'NsCommissions'),
            self::TYPE_PERCENTAGE => __m('Percentage', 'NsCommissions'),
        ];
    }

    /**
     * Get available calculation bases
     */
    public static function getCalculationBases(): array
    {
        return [
            self::BASE_FIXED => __m('Fixed (Ignore Discounts)', 'NsCommissions'),
            self::BASE_GROSS => __m('Gross (Before Discounts)', 'NsCommissions'),
            self::BASE_NET => __m('Net (After Discounts)', 'NsCommissions'),
        ];
    }

    /**
     * Categories relationship
     */
    public function categories()
    {
        return $this->hasMany(CommissionProductCategory::class, 'commission_id', 'id');
    }

    /**
     * Product-specific values relationship (for Fixed type)
     */
    public function productValues()
    {
        return $this->hasMany(CommissionProductValue::class, 'commission_id', 'id');
    }

    /**
     * Get product-specific value if exists
     */
    public function getProductValue(int $productId): ?float
    {
        $productValue = $this->productValues()
            ->where('product_id', $productId)
            ->first();

        return $productValue?->value;
    }

    /**
     * Check if this is an "On The House" type commission
     */
    public function isOnTheHouse(): bool
    {
        return $this->type === self::TYPE_ON_THE_HOUSE;
    }

    /**
     * Check if this is a Fixed type commission
     */
    public function isFixed(): bool
    {
        return $this->type === self::TYPE_FIXED;
    }

    /**
     * Check if this is a Percentage type commission
     */
    public function isPercentage(): bool
    {
        return $this->type === self::TYPE_PERCENTAGE;
    }

    /**
     * Scope for active commissions
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope for specific role
     */
    public function scopeForRole($query, $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
