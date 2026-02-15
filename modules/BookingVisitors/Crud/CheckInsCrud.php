<?php

namespace Modules\BookingVisitors\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Modules\BookingVisitors\Models\VisitEvent;
use Modules\BookingVisitors\Support\StoreContext;

class CheckInsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'bookingvisitors.checkins';

    protected $table = 'bookingvisitors_visit_events';

    protected $model = VisitEvent::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'bookingvisitors/checkins';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.bookingvisitors.read',
        'update' => false,
        'delete' => false,
    ];

    protected $showOptions = false;

    public $relations = [
        ['bookingvisitors_bookings as booking', 'bookingvisitors_visit_events.booking_id', '=', 'booking.id'],
    ];

    public $pick = [
        'booking' => ['uuid', 'customer_name'],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function hook($query): void
    {
        StoreContext::apply($query, 'bookingvisitors_visit_events.store_id');
        $query->where('bookingvisitors_visit_events.event_type', 'check_in');
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Check-ins', 'BookingVisitors'),
            list_description: __m('Track successful booking check-ins.', 'BookingVisitors'),
            no_entry: __m('No check-ins found.', 'BookingVisitors'),
            create_new: __m('Create', 'BookingVisitors'),
            create_title: __m('Create', 'BookingVisitors'),
            create_description: __m('Create', 'BookingVisitors'),
            edit_title: __m('Edit', 'BookingVisitors'),
            edit_description: __m('Edit', 'BookingVisitors'),
            back_to_list: __m('Back', 'BookingVisitors')
        );
    }

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(label: __m('Booking Ref', 'BookingVisitors'), identifier: 'booking_uuid', width: '160px'),
            CrudTable::column(label: __m('Customer', 'BookingVisitors'), identifier: 'booking_customer_name', width: '180px'),
            CrudTable::column(label: __m('Event', 'BookingVisitors'), identifier: 'event_type', width: '130px'),
            CrudTable::column(label: __m('Date', 'BookingVisitors'), identifier: 'created_at', width: '180px')
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->event_type = strtoupper((string) $entry->event_type);

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->route('bookingvisitors.checkins'),
            create: '',
            edit: '',
            post: '',
            put: ''
        );
    }
}

