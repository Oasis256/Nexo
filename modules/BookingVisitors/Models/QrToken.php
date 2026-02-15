<?php

namespace Modules\BookingVisitors\Models;

use App\Models\NsModel;

class QrToken extends NsModel
{
    protected $table = 'bookingvisitors_qr_tokens';

    protected $fillable = [
        'store_id',
        'booking_id',
        'scope',
        'token_hash',
        'expires_at',
        'used_at',
        'used_by',
        'revoked_at',
        'metadata',
        'author',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'booking_id' => 'integer',
        'used_by' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
        'author' => 'integer',
    ];
}

