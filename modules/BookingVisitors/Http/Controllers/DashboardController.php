<?php

namespace Modules\BookingVisitors\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Services\DateService;
use Modules\BookingVisitors\Crud\AuditLogsCrud;
use Modules\BookingVisitors\Crud\BookingsCrud;
use Modules\BookingVisitors\Crud\CheckInsCrud;
use Modules\BookingVisitors\Crud\GuestsCrud;
use Modules\BookingVisitors\Models\Booking;
use Modules\BookingVisitors\Models\BookingGuest;

class DashboardController extends BaseDashboardController
{
    public function __construct(DateService $dateService)
    {
        parent::__construct($dateService);
    }

    public function index()
    {
        return BookingsCrud::table([
            'title' => __m('Bookings Dashboard', 'BookingVisitors'),
            'description' => __m('Track bookings, check-ins, and guest access in one place.', 'BookingVisitors'),
            'src' => ns()->url('/api/crud/' . BookingsCrud::IDENTIFIER),
            'createUrl' => false,
        ]);
    }

    public function bookings()
    {
        return BookingsCrud::table();
    }

    public function createBooking()
    {
        return BookingsCrud::form();
    }

    public function editBooking(Booking $booking)
    {
        return BookingsCrud::form($booking);
    }

    public function checkins()
    {
        return CheckInsCrud::table();
    }

    public function guests()
    {
        return GuestsCrud::table();
    }

    public function createGuest()
    {
        return GuestsCrud::form();
    }

    public function editGuest(BookingGuest $guest)
    {
        return GuestsCrud::form($guest);
    }

    public function logs()
    {
        return AuditLogsCrud::table();
    }
}
