<?php

namespace Modules\BookingVisitors\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Modules\BookingVisitors\Models\BookingGuest;
use Modules\BookingVisitors\Support\StoreContext;

class GuestsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'bookingvisitors.guests';

    protected $table = 'bookingvisitors_booking_guests';

    protected $model = BookingGuest::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'bookingvisitors/guests';

    protected $permissions = [
        'create' => 'nexopos.bookingvisitors.create',
        'read' => 'nexopos.bookingvisitors.guest.access',
        'update' => 'nexopos.bookingvisitors.update',
        'delete' => 'nexopos.bookingvisitors.delete',
    ];

    public $fillable = [
        'store_id',
        'booking_id',
        'guest_name',
        'guest_phone',
        'status',
        'metadata',
        'author',
    ];

    public $relations = [
        ['bookingvisitors_bookings as booking', 'bookingvisitors_booking_guests.booking_id', '=', 'booking.id'],
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
        StoreContext::apply($query, 'bookingvisitors_booking_guests.store_id');
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Guest Access', 'BookingVisitors'),
            list_description: __m('Manage guest access records tied to bookings.', 'BookingVisitors'),
            no_entry: __m('No guest records found.', 'BookingVisitors'),
            create_new: __m('Add Guest', 'BookingVisitors'),
            create_title: __m('Add Guest', 'BookingVisitors'),
            create_description: __m('Register a guest against a booking.', 'BookingVisitors'),
            edit_title: __m('Edit Guest', 'BookingVisitors'),
            edit_description: __m('Update guest access details.', 'BookingVisitors'),
            back_to_list: __m('Back to guest access', 'BookingVisitors')
        );
    }

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(label: __m('Booking Ref', 'BookingVisitors'), identifier: 'booking_uuid', width: '160px'),
            CrudTable::column(label: __m('Customer', 'BookingVisitors'), identifier: 'booking_customer_name', width: '180px'),
            CrudTable::column(label: __m('Guest', 'BookingVisitors'), identifier: 'guest_name', width: '170px'),
            CrudTable::column(label: __m('Phone', 'BookingVisitors'), identifier: 'guest_phone', width: '140px'),
            CrudTable::column(label: __m('Status', 'BookingVisitors'), identifier: 'status', width: '120px')
        );
    }

    public function getForm($entry = null): array
    {
        return [
            'main' => [
                'label' => __m('Guest Name', 'BookingVisitors'),
                'name' => 'guest_name',
                'value' => $entry->guest_name ?? '',
                'validation' => 'required|string|max:190',
            ],
            'tabs' => [
                'general' => [
                    'label' => __m('General', 'BookingVisitors'),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'booking_id',
                            'label' => __m('Booking ID', 'BookingVisitors'),
                            'value' => (string) ($entry->booking_id ?? ''),
                            'validation' => 'required|integer|min:1',
                        ],
                        [
                            'type' => 'text',
                            'name' => 'guest_phone',
                            'label' => __m('Guest Phone', 'BookingVisitors'),
                            'value' => $entry->guest_phone ?? '',
                            'validation' => 'nullable|string|max:50',
                        ],
                        [
                            'type' => 'select',
                            'name' => 'status',
                            'label' => __m('Status', 'BookingVisitors'),
                            'value' => $entry->status ?? 'pending',
                            'validation' => 'required|in:pending,granted,denied',
                            'options' => [
                                ['label' => __m('Pending', 'BookingVisitors'), 'value' => 'pending'],
                                ['label' => __m('Granted', 'BookingVisitors'), 'value' => 'granted'],
                                ['label' => __m('Denied', 'BookingVisitors'), 'value' => 'denied'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function filterPostInputs($inputs)
    {
        $inputs['store_id'] = StoreContext::id();
        $inputs['author'] = auth()->id();
        $inputs['metadata'] = is_array($inputs['metadata'] ?? null) ? $inputs['metadata'] : [];

        return $inputs;
    }

    public function filterPutInputs($inputs, BookingGuest $entry)
    {
        $inputs['store_id'] = $entry->store_id;
        $inputs['author'] = $entry->author;
        $inputs['metadata'] = is_array($inputs['metadata'] ?? null) ? $inputs['metadata'] : ($entry->metadata ?? []);

        return $inputs;
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->action(
            identifier: 'edit',
            label: __m('Edit', 'BookingVisitors'),
            type: 'GOTO',
            url: ns()->route('bookingvisitors.guests.edit', ['guest' => $entry->id])
        );

        $entry->action(
            identifier: 'delete',
            label: __m('Delete', 'BookingVisitors'),
            type: 'DELETE',
            url: ns()->url('/api/crud/' . self::IDENTIFIER . '/' . $entry->id),
            confirm: [
                'message' => __m('Delete this guest entry?', 'BookingVisitors'),
            ]
        );

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->route('bookingvisitors.guests'),
            create: ns()->route('bookingvisitors.guests.create'),
            edit: ns()->url('dashboard/bookingvisitors/guests/edit/{id}'),
            post: ns()->url('api/crud/' . self::IDENTIFIER),
            put: ns()->url('api/crud/' . self::IDENTIFIER . '/{id}')
        );
    }
}

