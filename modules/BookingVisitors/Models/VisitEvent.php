<?php

namespace Modules\BookingVisitors\Models;

use App\Models\NsModel;

class VisitEvent extends NsModel
{
    protected $table = 'bookingvisitors_visit_events';

    protected $fillable = [
        'store_id',
        'booking_id',
        'event_type',
        'payload',
        'author',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'booking_id' => 'integer',
        'payload' => 'array',
        'author' => 'integer',
    ];
}

