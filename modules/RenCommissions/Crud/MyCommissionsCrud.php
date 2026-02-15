<?php

namespace Modules\RenCommissions\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Modules\RenCommissions\Models\OrderItemCommission;
use Modules\RenCommissions\Support\StoreContext;

class MyCommissionsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'rencommissions.my-commissions';

    protected $table = 'rencommissions_order_item_commissions';

    protected $model = OrderItemCommission::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'rencommissions/my-commissions';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.rencommissions.read.own',
        'update' => false,
        'delete' => false,
    ];

    protected $showOptions = false;

    protected $showCheckboxes = false;

    public $relations = [
        ['nexopos_orders as order', 'rencommissions_order_item_commissions.order_id', '=', 'order.id'],
        ['nexopos_products as product', 'rencommissions_order_item_commissions.product_id', '=', 'product.id'],
        ['rencommissions_types as type', 'rencommissions_order_item_commissions.type_id', '=', 'type.id'],
    ];

    public $pick = [
        'order' => ['code'],
        'product' => ['name'],
        'type' => ['name'],
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

        $query->where('rencommissions_order_item_commissions.earner_id', auth()->id());
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('My Commissions', 'RenCommissions'),
            list_description: __m('Review your own commission entries.', 'RenCommissions'),
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
        $entry->type_name = $entry->type_name ?: __m('N/A', 'RenCommissions');
        $entry->commission_method = ucfirst(str_replace('_', ' ', (string) $entry->commission_method));
        $entry->total_commission = ns()->currency->define((float) $entry->total_commission)->format();
        $entry->status = ucfirst((string) $entry->status);

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url('dashboard/rencommissions/my-commissions'),
            create: '',
            edit: '',
            post: '',
            put: ''
        );
    }

    public function getBulkActions(): array
    {
        return [];
    }
}
