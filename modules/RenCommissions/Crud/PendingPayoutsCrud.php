<?php

namespace Modules\RenCommissions\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Throwable;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Services\CommissionPayoutService;
use Modules\RenCommissions\Support\StoreContext;

class PendingPayoutsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'rencommissions.pending-payouts';

    protected $table = 'rencommissions_order_item_commissions';

    protected $model = OrderItemCommission::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'rencommissions/pending-payouts';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.rencommissions.manage.payouts',
        'update' => 'nexopos.rencommissions.manage.payouts',
        'delete' => false,
    ];

    protected $showOptions = false;

    public $relations = [
        ['nexopos_orders as order', 'rencommissions_order_item_commissions.order_id', '=', 'order.id'],
        ['nexopos_products as product', 'rencommissions_order_item_commissions.product_id', '=', 'product.id'],
        ['nexopos_users as earner', 'rencommissions_order_item_commissions.earner_id', '=', 'earner.id'],
    ];

    public $pick = [
        'order' => ['code'],
        'product' => ['name'],
        'earner' => ['username'],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function hook($query): void
    {
        $storeId = StoreContext::id();
        if ($storeId !== null) {
            $query->where('rencommissions_order_item_commissions.store_id', $storeId);
        }

        $query->where('rencommissions_order_item_commissions.status', 'pending');
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Pending Payouts', 'RenCommissions'),
            list_description: __m('Review pending commissions and post payouts.', 'RenCommissions'),
            no_entry: __m('No pending commissions found.', 'RenCommissions'),
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
            CrudTable::column(label: __m('Earner', 'RenCommissions'), identifier: 'earner_username', width: '170px'),
            CrudTable::column(label: __m('Amount', 'RenCommissions'), identifier: 'total_commission', width: '170px')
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->order_code = $entry->order_code ?: __m('N/A', 'RenCommissions');
        $entry->product_name = $entry->product_name ?: __m('N/A', 'RenCommissions');
        $entry->earner_username = $entry->earner_username ?: __m('N/A', 'RenCommissions');
        $entry->total_commission = ns()->currency->define((float) $entry->total_commission)->format();

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url('dashboard/rencommissions/pending-payouts'),
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
                'label' => __m('Post Payout For Selected', 'RenCommissions'),
                'identifier' => 'post_selected_payout',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => self::IDENTIFIER]),
                'confirm' => __m('Create payout for selected pending commissions?', 'RenCommissions'),
            ],
            [
                'label' => __m('Post Payout For All Pending', 'RenCommissions'),
                'identifier' => 'post_all_pending_payout',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => self::IDENTIFIER]),
                'confirm' => __m('Create payout for all pending commissions?', 'RenCommissions'),
            ],
        ];
    }

    public function bulkAction(Request $request)
    {
        ns()->restrict('nexopos.rencommissions.manage.payouts');

        $action = (string) $request->input('action');
        $ids = collect($request->input('entries', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($action === 'post_all_pending_payout') {
            $query = StoreContext::constrain(OrderItemCommission::query());

            $ids = $query
                ->where('status', 'pending')
                ->pluck('id');
        }

        if ($action === 'post_selected_payout' && $ids->isEmpty()) {
            return [
                'status' => 'info',
                'message' => __m('No entries selected.', 'RenCommissions'),
            ];
        }

        if ($ids->isEmpty()) {
            return [
                'status' => 'info',
                'message' => __m('No pending commissions found.', 'RenCommissions'),
            ];
        }

        try {
            $payout = app(CommissionPayoutService::class)->create($ids->all(), auth()->id(), __m('Posted from pending payouts list.', 'RenCommissions'));
        } catch (Throwable $exception) {
            return [
                'status' => 'danger',
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'status' => 'success',
            'message' => sprintf(__m('Payout %s created.', 'RenCommissions'), $payout->reference),
        ];
    }
}
