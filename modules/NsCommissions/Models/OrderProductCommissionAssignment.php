<?php

namespace Modules\NsCommissions\Models;

use App\Models\NsModel;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\User;

class OrderProductCommissionAssignment extends NsModel
{
    protected $table = 'nexopos_order_product_commission_assignments';

    protected $fillable = [
        'order_id',
        'order_product_id',
        'user_id',
        'commission_id',
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
     * User (commission earner) relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Commission relationship
     */
    public function commission()
    {
        return $this->belongsTo(Commission::class, 'commission_id', 'id');
    }

    /**
     * Scope for specific order
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
