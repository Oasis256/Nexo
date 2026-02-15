<?php

namespace Modules\BookingVisitors\Models;

use App\Models\NsModel;

class ChannelMessage extends NsModel
{
    protected $table = 'bookingvisitors_channel_messages';

    protected $fillable = [
        'store_id',
        'booking_id',
        'channel',
        'message_type',
        'recipient',
        'status',
        'provider_ref',
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

