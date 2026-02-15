<?php

namespace Modules\RenCommissions\Crud;

use App\Classes\CrudTable;
use App\Services\Helper;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Services\CommissionPayoutService;
use Modules\RenCommissions\Support\StoreContext;

class AllCommissionsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'rencommissions.commissions';

    protected $table = 'rencommissions_order_item_commissions';

    protected $model = OrderItemCommission::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'rencommissions/commissions';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.rencommissions.read.commissions',
        'update' => 'nexopos.rencommissions.update.commissions',
        'delete' => false,
    ];

    protected $showOptions = false;

    public $relations = [
        ['nexopos_orders as order', 'rencommissions_order_item_commissions.order_id', '=', 'order.id'],
        ['nexopos_products as product', 'rencommissions_order_item_commissions.product_id', '=', 'product.id'],
        ['nexopos_users as earner', 'rencommissions_order_item_commissions.earner_id', '=', 'earner.id'],
        ['rencommissions_types as type', 'rencommissions_order_item_commissions.type_id', '=', 'type.id'],
    ];

    public $pick = [
        'order' => ['code'],
        'product' => ['name'],
        'earner' => ['username'],
        'type' => ['name'],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->queryFilters = [
            [
                'type' => 'select',
                'name' => 'status',
                'label' => __m('Status', 'RenCommissions'),
                'description' => __m('Filter by commission status.', 'RenCommissions'),
                'options' => Helper::kvToJsOptions([
                    'pending' => __m('Pending', 'RenCommissions'),
                    'paid' => __m('Paid', 'RenCommissions'),
                    'voided' => __m('Voided', 'RenCommissions'),
                    'cancelled' => __m('Cancelled', 'RenCommissions'),
                ]),
            ],
            [
                'type' => 'daterangepicker',
                'name' => 'created_at',
                'label' => __m('Created Between', 'RenCommissions'),
                'description' => __m('Filter by commission creation date.', 'RenCommissions'),
            ],
        ];
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('All Commissions', 'RenCommissions'),
            list_description: __m('Browse and manage all commission records.', 'RenCommissions'),
            no_entry: __m('No commissions found.', 'RenCommissions'),
            create_new: __m('Create', 'RenCommissions'),
            create_title: __m('Create', 'RenCommissions'),
            create_description: __m('Create', 'RenCommissions'),
            edit_title: __m('Edit', 'RenCommissions'),
            edit_description: __m('Edit', 'RenCommissions'),
            back_to_list: __m('Back', 'RenCommissions')
        );
    }

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(label: __m('Date', 'RenCommissions'), identifier: 'created_at', width: '170px'),
            CrudTable::column(label: __m('Order', 'RenCommissions'), identifier: 'order_code', width: '140px'),
            CrudTable::column(label: __m('Product', 'RenCommissions'), identifier: 'product_name', width: '220px'),
            CrudTable::column(label: __m('Earner', 'RenCommissions'), identifier: 'earner_username', width: '160px'),
            CrudTable::column(label: __m('Type', 'RenCommissions'), identifier: 'type_name', width: '140px'),
            CrudTable::column(label: __m('Method', 'RenCommissions'), identifier: 'commission_method', width: '130px'),
            CrudTable::column(label: __m('Amount', 'RenCommissions'), identifier: 'total_commission', width: '150px'),
            CrudTable::column(label: __m('Status', 'RenCommissions'), identifier: 'status', width: '120px')
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->order_code = $entry->order_code ?: __m('N/A', 'RenCommissions');
        $entry->product_name = $entry->product_name ?: __m('N/A', 'RenCommissions');
        $entry->earner_username = $entry->earner_username ?: __m('N/A', 'RenCommissions');
        $entry->type_name = $entry->type_name ?: __m('N/A', 'RenCommissions');
        $entry->commission_method = ucfirst(str_replace('_', ' ', (string) $entry->commission_method));
        $entry->total_commission = ns()->currency->define((float) $entry->total_commission)->format();
        $entry->status = ucfirst((string) $entry->status);

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url('dashboard/rencommissions/commissions'),
            create: '',
            edit: '',
            post: '',
            put: ''
        );
    }

    public function getBulkActions(): array
    {
        return [
            [
                'label' => __m('Mark Selected Paid', 'RenCommissions'),
                'identifier' => 'mark_paid_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => self::IDENTIFIER]),
                'confirm' => __m('Mark selected commissions as paid?', 'RenCommissions'),
            ],
            [
                'label' => __m('Void Selected', 'RenCommissions'),
                'identifier' => 'void_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => self::IDENTIFIER]),
                'confirm' => __m('Void selected commissions?', 'RenCommissions'),
            ],
        ];
    }

    public function bulkAction(Request $request)
    {
        ns()->restrict('nexopos.rencommissions.update.commissions');

        $ids = collect($request->input('entries', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return [
                'status' => 'info',
                'message' => __m('No entries selected.', 'RenCommissions'),
            ];
        }

        $query = StoreContext::constrain(OrderItemCommission::query())
            ->whereIn('id', $ids->all())
            ->where('status', 'pending');

        if ($request->input('action') === 'mark_paid_selected') {
            $pendingIds = (clone $query)->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (empty($pendingIds)) {
                return [
                    'status' => 'info',
                    'message' => __m('No pending entries selected.', 'RenCommissions'),
                ];
            }

            app(CommissionPayoutService::class)->create(
                $pendingIds,
                auth()->id(),
                __m('Bulk mark paid from all commissions.', 'RenCommissions')
            );

            return [
                'status' => 'success',
                'message' => sprintf(__m('%s commission(s) marked paid.', 'RenCommissions'), count($pendingIds)),
            ];
        }

        if ($request->input('action') === 'void_selected') {
            $count = (clone $query)->update([
                'status' => 'voided',
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => __m('Voided in bulk action.', 'RenCommissions'),
                'updated_at' => now(),
            ]);

            return [
                'status' => 'success',
                'message' => sprintf(__m('%s commission(s) voided.', 'RenCommissions'), $count),
            ];
        }

        return false;
    }

    public function hook($query): void
    {
        $storeId = StoreContext::id();
        if ($storeId !== null) {
            $query->where('rencommissions_order_item_commissions.store_id', $storeId);
        }

        $request = app(Request::class);
        $biweekly = (string) $request->query('biweekly', '');
        $dailyDate = (string) $request->query('daily_date', '');
        $scope = (string) $request->query('scope', 'global');
        $earnerId = (int) $request->query('earner_id', 0);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dailyDate)) {
            $query->whereDate('rencommissions_order_item_commissions.created_at', $dailyDate);
            $anchor = Carbon::parse($dailyDate);
        } else {
            $anchor = now();
        }

        if ($biweekly === 'first_half') {
            $query->whereBetween('rencommissions_order_item_commissions.created_at', [
                $anchor->copy()->startOfMonth()->startOfDay(),
                $anchor->copy()->startOfMonth()->day(14)->endOfDay(),
            ]);
        } elseif ($biweekly === 'second_half') {
            $query->whereBetween('rencommissions_order_item_commissions.created_at', [
                $anchor->copy()->startOfMonth()->day(15)->startOfDay(),
                $anchor->copy()->endOfMonth()->endOfDay(),
            ]);
        }

        if ($scope === 'earner' && $earnerId > 0) {
            $query->where('rencommissions_order_item_commissions.earner_id', $earnerId);
        }
    }
}
