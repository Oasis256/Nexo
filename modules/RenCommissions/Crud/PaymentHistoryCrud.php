<?php

namespace Modules\RenCommissions\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Modules\RenCommissions\Models\CommissionPayout;
use Modules\RenCommissions\Support\StoreContext;

class PaymentHistoryCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'rencommissions.payment-history';

    protected $table = 'rencommissions_payouts';

    protected $model = CommissionPayout::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'rencommissions/payment-history';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.rencommissions.read.reports',
        'update' => false,
        'delete' => false,
    ];

    protected $showOptions = true;

    protected $showCheckboxes = false;

    public $relations = [
        ['nexopos_users as creator', 'rencommissions_payouts.created_by', '=', 'creator.id'],
    ];

    public $pick = [
        'creator' => ['username'],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function hook($query): void
    {
        StoreContext::constrain($query, 'rencommissions_payouts.store_id');
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Payment History', 'RenCommissions'),
            list_description: __m('Track all posted commission payouts.', 'RenCommissions'),
            no_entry: __m('No payment history found.', 'RenCommissions'),
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
            CrudTable::column(label: __m('Reference', 'RenCommissions'), identifier: 'reference', width: '190px'),
            CrudTable::column(label: __m('Period Start', 'RenCommissions'), identifier: 'period_start', width: '170px'),
            CrudTable::column(label: __m('Period End', 'RenCommissions'), identifier: 'period_end', width: '170px'),
            CrudTable::column(label: __m('Entries', 'RenCommissions'), identifier: 'entries_count', width: '120px'),
            CrudTable::column(label: __m('Amount', 'RenCommissions'), identifier: 'total_amount', width: '160px'),
            CrudTable::column(label: __m('Status', 'RenCommissions'), identifier: 'status', width: '130px'),
            CrudTable::column(label: __m('Created By', 'RenCommissions'), identifier: 'creator_username', width: '140px'),
            CrudTable::column(label: __m('Created At', 'RenCommissions'), identifier: 'created_at', width: '170px')
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->reference = $entry->reference ?: __m('N/A', 'RenCommissions');
        $entry->creator_username = $entry->creator_username ?: __m('N/A', 'RenCommissions');
        $entry->total_amount = ns()->currency->define((float) $entry->total_amount)->format();
        $entry->status = ucfirst((string) $entry->status);
        $entry->action(
            label: __m('Print', 'RenCommissions'),
            identifier: 'print',
            url: ns()->route('rencommissions.history.print', ['payoutId' => (int) $entry->id]),
            confirm: null,
            type: 'GOTO'
        );

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url('dashboard/rencommissions/payment-history'),
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
