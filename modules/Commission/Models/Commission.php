<?php

namespace Modules\Commission\Models;

use App\Models\NsModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    const BASE_FIXED = 'fixed';
    const BASE_GROSS = 'gross';
    const BASE_NET = 'net';

    protected $fillable = [
        'name',
        'active',
        'type',
        'calculation_base',
        'value',
        'role_id',
        'description',
        'author',
    ];

    protected $casts = [
        'active' => 'boolean',
        'value' => 'decimal:5',
    ];

    /**
     * Get available commission types with labels
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ON_THE_HOUSE => __m('On The House', 'Commission'),
            self::TYPE_FIXED => __m('Fixed', 'Commission'),
            self::TYPE_PERCENTAGE => __m('Percentage', 'Commission'),
        ];
    }

    /**
     * Get available calculation bases with labels
     */
    public static function getCalculationBases(): array
    {
        return [
            self::BASE_FIXED => __m('Fixed (Unit Price)', 'Commission'),
            self::BASE_GROSS => __m('Gross (Before Discounts)', 'Commission'),
            self::BASE_NET => __m('Net (After Discounts)', 'Commission'),
        ];
    }

    /**
     * Author relationship
     */
    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author', 'id');
    }

    /**
     * Role relationship
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    /**
     * Categories relationship
     */
    public function categories(): HasMany
    {
        return $this->hasMany(CommissionCategory::class, 'commission_id', 'id');
    }

    /**
     * Product-specific values relationship (for Fixed type)
     */
    public function productValues(): HasMany
    {
        return $this->hasMany(CommissionProductValue::class, 'commission_id', 'id');
    }

    /**
     * Earned commissions relationship
     */
    public function earnedCommissions(): HasMany
    {
        return $this->hasMany(EarnedCommission::class, 'commission_id', 'id');
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

    /**
     * Scope for commissions that apply to a category
     */
    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where(function ($q) use ($categoryId) {
            $q->whereHas('categories', function ($sq) use ($categoryId) {
                $sq->where('category_id', $categoryId);
            })->orWhereDoesntHave('categories');
        });
    }
}
