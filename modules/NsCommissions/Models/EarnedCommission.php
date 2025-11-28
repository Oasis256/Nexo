<?php

namespace Modules\NsCommissions\Models;

use App\Models\NsModel;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;

class EarnedCommission extends NsModel
{
    protected $table = 'nexopos_orders_commissions';

    protected $fillable = [
        'name',
        'value',
        'user_id',
        'order_id',
        'order_product_id',
        'product_id',
        'quantity',
        'commission_id',
        'commission_type',
        'base_amount',
        'author',
    ];

    protected $casts = [
        'value' => 'decimal:5',
        'quantity' => 'decimal:5',
        'base_amount' => 'decimal:5',
    ];

    /**
     * Order relationship
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Order Product relationship
     */
    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'id');
    }

    /**
     * Product relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * User (commission earner) relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Commission definition relationship
     */
    public function commission()
    {
        return $this->belongsTo(Commission::class, 'commission_id', 'id');
    }

    /**
     * Scope for specific user
     */
    public function scopeFor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific user (alias)
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific order
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope for specific commission type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('commission_type', $type);
    }

    /**
     * Scope for date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
