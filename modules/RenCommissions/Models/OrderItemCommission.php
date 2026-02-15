<?php

namespace Modules\RenCommissions\Models;

use App\Models\NsModel;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\User;

class OrderItemCommission extends NsModel
{
    protected $table = 'rencommissions_order_item_commissions';

    protected $fillable = [
        'store_id',
        'order_id',
        'order_product_id',
        'product_id',
        'earner_id',
        'type_id',
        'commission_method',
        'commission_value',
        'unit_price',
        'quantity',
        'total_commission',
        'status',
        'assigned_by',
        'paid_at',
        'paid_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'payout_id',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'order_id' => 'integer',
        'order_product_id' => 'integer',
        'product_id' => 'integer',
        'earner_id' => 'integer',
        'type_id' => 'integer',
        'commission_value' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'assigned_by' => 'integer',
        'paid_by' => 'integer',
        'voided_by' => 'integer',
        'payout_id' => 'integer',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function orderProduct()
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function earner()
    {
        return $this->belongsTo(User::class, 'earner_id', 'id');
    }

    public function type()
    {
        return $this->belongsTo(CommissionType::class, 'type_id', 'id');
    }

    public function payout()
    {
        return $this->belongsTo(CommissionPayout::class, 'payout_id', 'id');
    }
}
