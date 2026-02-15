<?php

namespace Modules\RenCommissions\Models;

use App\Models\NsModel;

class CommissionType extends NsModel
{
    protected $table = 'rencommissions_types';

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'calculation_method',
        'default_value',
        'min_value',
        'max_value',
        'is_active',
        'priority',
        'author',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'default_value' => 'decimal:2',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'author' => 'integer',
    ];
}
