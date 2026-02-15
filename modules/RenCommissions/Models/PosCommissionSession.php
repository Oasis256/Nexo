<?php

namespace Modules\RenCommissions\Models;

use App\Models\NsModel;

class PosCommissionSession extends NsModel
{
    protected $table = 'rencommissions_pos_sessions';

    protected $fillable = [
        'store_id',
        'session_id',
        'product_index',
        'product_id',
        'earner_id',
        'type_id',
        'commission_method',
        'commission_value',
        'unit_price',
        'quantity',
        'total_commission',
        'assigned_by',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'product_index' => 'integer',
        'product_id' => 'integer',
        'earner_id' => 'integer',
        'type_id' => 'integer',
        'commission_value' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'assigned_by' => 'integer',
    ];
}
