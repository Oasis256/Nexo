<?php

namespace Modules\BookingVisitors\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Modules\BookingVisitors\Models\Booking;
use Modules\BookingVisitors\Support\StoreContext;

class BookingsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'bookingvisitors.bookings';

    protected $table = 'bookingvisitors_bookings';

    protected $model = Booking::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'bookingvisitors/bookings';

    protected $permissions = [
        'create' => 'nexopos.bookingvisitors.create',
        'read' => 'nexopos.bookingvisitors.read',
        'update' => 'nexopos.bookingvisitors.update',
        'delete' => 'nexopos.bookingvisitors.delete',
    ];

    public $fillable = [
        'store_id',
        'channel',
        'status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'start_at',
        'end_at',
        'notes',
        'metadata',
        'author',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function hook($query): void
    {
        StoreContext::apply($query);
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Bookings', 'BookingVisitors'),
            list_description: __m('Manage bookings from phone, website and WhatsApp Business API.', 'BookingVisitors'),
            no_entry: __m('No bookings found.', 'BookingVisitors'),
            create_new: __m('Create Booking', 'BookingVisitors'),
            create_title: __m('Create Booking', 'BookingVisitors'),
            create_description: __m('Register a new booking manually.', 'BookingVisitors'),
            edit_title: __m('Edit Booking', 'BookingVisitors'),
            edit_description: __m('Update booking details.', 'BookingVisitors'),
            back_to_list: __m('Back to bookings', 'BookingVisitors')
        );
    }

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(label: __m('Reference', 'BookingVisitors'), identifier: 'uuid', width: '130px'),
            CrudTable::column(label: __m('Customer', 'BookingVisitors'), identifier: 'customer_name', width: '180px'),
            CrudTable::column(label: __m('Channel', 'BookingVisitors'), identifier: 'channel', width: '150px'),
            CrudTable::column(label: __m('Start', 'BookingVisitors'), identifier: 'start_at', width: '170px'),
            CrudTable::column(label: __m('End', 'BookingVisitors'), identifier: 'end_at', width: '170px'),
            CrudTable::column(label: __m('Status', 'BookingVisitors'), identifier: 'status', width: '120px')
        );
    }

    public function getForm($entry = null): array
    {
        return [
            'main' => [
                'label' => __m('Customer Name', 'BookingVisitors'),
                'name' => 'customer_name',
                'value' => $entry->customer_name ?? '',
                'description' => __m('Primary client name.', 'BookingVisitors'),
                'validation' => 'required|string|max:190',
            ],
            'tabs' => [
                'general' => [
                    'label' => __m('General', 'BookingVisitors'),
                    'fields' => [
                        [
                            'type' => 'select',
                            'name' => 'channel',
                            'label' => __m('Channel', 'BookingVisitors'),
                            'value' => $entry->channel ?? 'phone',
                            'validation' => 'required|in:phone,website,whatsapp_business_api',
                            'options' => [
                                ['label' => __m('Phone', 'BookingVisitors'), 'value' => 'phone'],
                                ['label' => __m('Website', 'BookingVisitors'), 'value' => 'website'],
                                ['label' => __m('WhatsApp Business API', 'BookingVisitors'), 'value' => 'whatsapp_business_api'],
                            ],
                        ],
                        [
                            'type' => 'text',
                            'name' => 'customer_phone',
                            'label' => __m('Phone', 'BookingVisitors'),
                            'value' => $entry->customer_phone ?? '',
                            'validation' => 'nullable|string|max:50',
                        ],
                        [
                            'type' => 'text',
                            'name' => 'customer_email',
                            'label' => __m('Email', 'BookingVisitors'),
                            'value' => $entry->customer_email ?? '',
                            'validation' => 'nullable|email|max:190',
                        ],
                        [
                            'type' => 'datetime',
                            'name' => 'start_at',
                            'label' => __m('Start At', 'BookingVisitors'),
                            'value' => optional($entry->start_at)->format('Y-m-d H:i:s') ?? '',
                            'validation' => 'required|date',
                        ],
                        [
                            'type' => 'datetime',
                            'name' => 'end_at',
                            'label' => __m('End At', 'BookingVisitors'),
                            'value' => optional($entry->end_at)->format('Y-m-d H:i:s') ?? '',
                            'validation' => 'required|date|after:start_at',
                        ],
                        [
                            'type' => 'select',
                            'name' => 'status',
                            'label' => __m('Status', 'BookingVisitors'),
                            'value' => $entry->status ?? 'confirmed',
                            'validation' => 'required|in:draft,confirmed,checked_in,completed,cancelled',
                            'options' => [
                                ['label' => __m('Draft', 'BookingVisitors'), 'value' => 'draft'],
                                ['label' => __m('Confirmed', 'BookingVisitors'), 'value' => 'confirmed'],
                                ['label' => __m('Checked-in', 'BookingVisitors'), 'value' => 'checked_in'],
                                ['label' => __m('Completed', 'BookingVisitors'), 'value' => 'completed'],
                                ['label' => __m('Cancelled', 'BookingVisitors'), 'value' => 'cancelled'],
                            ],
                        ],
                        [
                            'type' => 'textarea',
                            'name' => 'notes',
                            'label' => __m('Notes', 'BookingVisitors'),
                            'value' => $entry->notes ?? '',
                            'validation' => 'nullable|string|max:1000',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function beforePost($request)
    {
        $request['store_id'] = StoreContext::id();
        $request['author'] = auth()->id();
        $request['uuid'] = strtoupper(\Illuminate\Support\Str::random(10));
        $request['confirmed_at'] = ($request['status'] ?? 'confirmed') === 'confirmed' ? now() : null;

        return $request;
    }

    public function filterPostInputs($inputs)
    {
        $inputs['store_id'] = StoreContext::id();
        $inputs['author'] = auth()->id();
        $inputs['metadata'] = is_array($inputs['metadata'] ?? null) ? $inputs['metadata'] : [];

        return $inputs;
    }

    public function filterPutInputs($inputs, Booking $entry)
    {
        $inputs['store_id'] = $entry->store_id;
        $inputs['author'] = $entry->author;
        $inputs['metadata'] = is_array($inputs['metadata'] ?? null) ? $inputs['metadata'] : ($entry->metadata ?? []);

        return $inputs;
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->channel = str_replace('_', ' ', ucfirst((string) $entry->channel));
        $entry->start_at = $entry->start_at ? date('Y-m-d H:i', strtotime((string) $entry->start_at)) : '-';
        $entry->end_at = $entry->end_at ? date('Y-m-d H:i', strtotime((string) $entry->end_at)) : '-';

        $entry->action(
            identifier: 'edit',
            label: __m('Edit', 'BookingVisitors'),
            type: 'GOTO',
            url: ns()->route('bookingvisitors.bookings.edit', ['booking' => $entry->id])
        );

        $entry->action(
            identifier: 'delete',
            label: __m('Delete', 'BookingVisitors'),
            type: 'DELETE',
            url: ns()->url('/api/crud/' . self::IDENTIFIER . '/' . $entry->id),
            confirm: [
                'message' => __m('Delete this booking?', 'BookingVisitors'),
            ]
        );

        return $entry;
    }

    public function bulkAction(Request $request)
    {
        if ($request->input('action') === 'cancel_selected') {
            $ids = collect($request->input('entries', []))->map(fn ($id) => (int) $id)->filter()->values();

            if ($ids->isEmpty()) {
                return [
                    'status' => 'info',
                    'message' => __m('No bookings selected.', 'BookingVisitors'),
                ];
            }

            Booking::query()
                ->whereIn('id', $ids->all())
                ->when(StoreContext::id() !== null, fn ($query) => $query->where('store_id', StoreContext::id()))
                ->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

            return [
                'status' => 'success',
                'message' => __m('Selected bookings cancelled.', 'BookingVisitors'),
            ];
        }

        return false;
    }

    public function getBulkActions(): array
    {
        return [
            [
                'label' => __m('Cancel Selected', 'BookingVisitors'),
                'identifier' => 'cancel_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => self::IDENTIFIER]),
                'confirm' => __m('Cancel selected bookings?', 'BookingVisitors'),
            ],
        ];
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->route('bookingvisitors.bookings'),
            create: ns()->route('bookingvisitors.bookings.create'),
            edit: ns()->url('dashboard/bookingvisitors/bookings/edit/{id}'),
            post: ns()->url('api/crud/' . self::IDENTIFIER),
            put: ns()->url('api/crud/' . self::IDENTIFIER . '/{id}')
        );
    }
}

