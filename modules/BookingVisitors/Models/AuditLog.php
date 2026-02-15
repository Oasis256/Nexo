<?php

namespace Modules\BookingVisitors\Models;

use App\Models\NsModel;

class AuditLog extends NsModel
{
    protected $table = 'bookingvisitors_audit_logs';

    protected $fillable = [
        'store_id',
        'entity_type',
        'entity_id',
        'action',
        'payload',
        'author',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'entity_id' => 'integer',
        'payload' => 'array',
        'author' => 'integer',
    ];
}

