<?php

namespace Modules\Commission\Models;

use App\Models\NsModel;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionAssignment extends NsModel
{
    protected $table = 'nexopos_commission_assignments';

    protected $fillable = [
        'order_id',
        'order_product_id',
        'user_id',
        'commission_id',
    ];

    /**
     * Order relationship
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * Order Product relationship
     */
    public function orderProduct(): BelongsTo
    {
        return $this->belongsTo(OrderProduct::class, 'order_product_id', 'id');
    }

    /**
     * User (assigned earner) relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Commission definition relationship
     */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(Commission::class, 'commission_id', 'id');
    }
}
