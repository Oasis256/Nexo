<?php

namespace Modules\RenCommissions\Models;

use App\Classes\Hook;
use App\Models\NsModel;
use App\Models\Product;
use App\Models\User;

/**
 * POS Commission Session Model
 * 
 * Temporary storage for commission assignments during POS cart building.
 * Records are converted to OrderItemCommission when order is finalized.
 * Multistore-aware: table name is dynamically prefixed based on active store.
 *
 * @property int $id
 * @property string $session_id
 * @property int $product_index
 * @property int $product_id
 * @property int $staff_id
 * @property string $commission_type
 * @property float $commission_value
 * @property float $commission_amount
 * @property float $unit_price
 * @property float $quantity
 * @property float $total_price
 * @property float $total_commission
 * @property int $assigned_by
 * @property string $created_at
 * @property string $updated_at
 */
class PosCommissionSession extends NsModel
{
    /**
     * Base table name (without multistore prefix)
     */
    protected $table = 'rencommissions_pos_sessions';

    /**
     * Fillable fields
     */
    protected $fillable = [
        'session_id',
        'product_index',
        'product_id',
        'staff_id',
        'commission_type',
        'commission_value',
        'commission_amount',
        'unit_price',
        'quantity',
        'total_price',
        'total_commission',
        'assigned_by',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'product_index' => 'integer',
        'product_id' => 'integer',
        'staff_id' => 'integer',
        'commission_value' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total_price' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'assigned_by' => 'integer',
    ];

    /**
     * Constructor - table name filtered by NsModel for multistore support
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Relationship: Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Relationship: Staff member (earner)
     */
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }

    /**
     * Relationship: User who assigned the commission
     */
    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /**
     * Scope: By session ID
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: By product index (position in cart)
     */
    public function scopeByProductIndex($query, int $index)
    {
        return $query->where('product_index', $index);
    }

    /**
     * Scope: By product ID
     */
    public function scopeByProductId($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: Old sessions (for cleanup)
     */
    public function scopeOlderThan($query, $hours = 24)
    {
        return $query->where('created_at', '<', now()->subHours($hours));
    }

    /**
     * Get all commissions for a specific session
     */
    public static function getBySessionId(string $sessionId)
    {
        return static::bySession($sessionId)
            ->orderBy('product_index', 'asc')
            ->get();
    }

    /**
     * Get commission for specific product in session by product ID
     */
    public static function getForProduct(string $sessionId, int $productId)
    {
        return static::bySession($sessionId)
            ->byProductId($productId)
            ->first();
    }

    /**
     * Clear all commissions for a session
     */
    public static function clearSession(string $sessionId): int
    {
        return static::bySession($sessionId)->delete();
    }

    /**
     * Calculate totals for a session
     */
    public static function getSessionTotals(string $sessionId): array
    {
        $records = static::bySession($sessionId)->get();
        
        return [
            'count' => $records->count(),
            'total_commission' => $records->sum('total_commission'),
            'total_price' => $records->sum('total_price'),
        ];
    }

    /**
     * Update product index when cart items are reordered
     */
    public static function updateProductIndex(string $sessionId, int $oldIndex, int $newIndex): bool
    {
        return static::bySession($sessionId)
            ->byProductIndex($oldIndex)
            ->update(['product_index' => $newIndex]) > 0;
    }

    /**
     * Remove commission when product is removed from cart
     */
    public static function removeByProductIndex(string $sessionId, int $productIndex): bool
    {
        return static::bySession($sessionId)
            ->byProductIndex($productIndex)
            ->delete() > 0;
    }
}
