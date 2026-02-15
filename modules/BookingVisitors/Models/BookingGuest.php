<?php

namespace Modules\BookingVisitors\Models;

use App\Models\NsModel;

class BookingGuest extends NsModel
{
    protected $table = 'bookingvisitors_booking_guests';

    protected $fillable = [
        'store_id',
        'booking_id',
        'guest_name',
        'guest_phone',
        'status',
        'metadata',
        'author',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'booking_id' => 'integer',
        'metadata' => 'array',
        'author' => 'integer',
    ];
}

