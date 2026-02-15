<?php

namespace Modules\BookingVisitors\Models;

use App\Models\NsModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends NsModel
{
    protected $table = 'bookingvisitors_bookings';

    protected $fillable = [
        'store_id',
        'uuid',
        'channel',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'start_at',
        'end_at',
        'confirmed_at',
        'checked_in_at',
        'completed_at',
        'cancelled_at',
        'notes',
        'metadata',
        'author',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'author' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function guests(): HasMany
    {
        return $this->hasMany(BookingGuest::class, 'booking_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(QrToken::class, 'booking_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(VisitEvent::class, 'booking_id');
    }
}
