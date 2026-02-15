<?php

namespace Modules\RenCommissions\Models;

use App\Classes\Hook;
use App\Models\NsModel;
use App\Models\User;

/**
 * Commission Type Model
 * 
 * Represents commission type definitions (percentage, fixed, on_the_house)
 * Multistore-aware: table name is dynamically prefixed based on active store
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $calculation_method (percentage|fixed|on_the_house)
 * @property float $default_value
 * @property float $min_value
 * @property float $max_value
 * @property bool $is_active
 * @property bool $apply_to_discounted
 * @property bool $requires_approval
 * @property int $priority
 * @property int $author
 * @property string $created_at
 * @property string $updated_at
 */
class CommissionType extends NsModel
{
    /**
     * Base table name (without multistore prefix)
     */
    protected $table = 'rencommissions_types';

    /**
     * Fillable fields
     */
    protected $fillable = [
        'name',
        'description',
        'calculation_method',
        'default_value',
        'min_value',
        'max_value',
        'is_active',
        'apply_to_discounted',
        'requires_approval',
        'priority',
        'author',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'default_value' => 'decimal:2',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'is_active' => 'boolean',
        'apply_to_discounted' => 'boolean',
        'requires_approval' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Calculation method constants
     */
    const METHOD_PERCENTAGE = 'percentage';
    const METHOD_FIXED = 'fixed';
    const METHOD_ON_THE_HOUSE = 'on_the_house';

    /**
     * Constructor - sets dynamic table name for multistore
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        
        // Table name is filtered by NsModel parent via 'ns-model-table' hook
        // NsMultiStore will prefix with store_{id}_ when active
    }

    /**
     * Scope: Active types only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By calculation method
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('calculation_method', $method);
    }

    /**
     * Scope: Ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Relationship: Author (user who created)
     */
    public function authorUser()
    {
        return $this->belongsTo(User::class, 'author', 'id');
    }

    /**
     * Check if type is percentage-based
     */
    public function isPercentage(): bool
    {
        return $this->calculation_method === self::METHOD_PERCENTAGE;
    }

    /**
     * Check if type is fixed amount
     */
    public function isFixed(): bool
    {
        return $this->calculation_method === self::METHOD_FIXED;
    }

    /**
     * Check if type is on-the-house
     */
    public function isOnTheHouse(): bool
    {
        return $this->calculation_method === self::METHOD_ON_THE_HOUSE;
    }

    /**
     * Validate a value against min/max constraints
     */
    public function validateValue($value): bool
    {
        if ($this->min_value !== null && $value < $this->min_value) {
            return false;
        }
        
        if ($this->max_value !== null && $value > $this->max_value) {
            return false;
        }
        
        return true;
    }

    /**
     * Get available calculation methods
     */
    public static function getCalculationMethods(): array
    {
        return [
            self::METHOD_PERCENTAGE => __('Percentage'),
            self::METHOD_FIXED => __('Fixed Amount'),
            self::METHOD_ON_THE_HOUSE => __('On-The-House'),
        ];
    }
}
