<?php

namespace Modules\RenCommissions\Models;

use App\Models\NsModel;

class CommissionPayout extends NsModel
{
    protected $table = 'rencommissions_payouts';

    protected $fillable = [
        'store_id',
        'reference',
        'period_start',
        'period_end',
        'total_amount',
        'entries_count',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'total_amount' => 'decimal:2',
        'entries_count' => 'integer',
        'created_by' => 'integer',
    ];
}
