<?php

namespace Modules\RenCommissions\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\RenCommissions\Support\StoreContext;

class StaffEarningsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'rencommissions.staff-earnings';

    protected $table = 'rencommissions_order_item_commissions';

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'rencommissions/staff-earnings';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.rencommissions.read.reports',
        'update' => false,
        'delete' => false,
    ];

    protected $showOptions = false;

    protected $showCheckboxes = false;

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Staff Earnings', 'RenCommissions'),
            list_description: __m('Compare commission totals by earner.', 'RenCommissions'),
            no_entry: __m('No staff earnings found.', 'RenCommissions'),
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
            CrudTable::column(label: __m('Earner', 'RenCommissions'), identifier: 'earner_username', width: '220px'),
            CrudTable::column(label: __m('Total', 'RenCommissions'), identifier: 'total_amount', width: '180px'),
            CrudTable::column(label: __m('Pending', 'RenCommissions'), identifier: 'pending_amount', width: '180px'),
            CrudTable::column(label: __m('Paid', 'RenCommissions'), identifier: 'paid_amount', width: '180px'),
            CrudTable::column(label: __m('Count', 'RenCommissions'), identifier: 'rows_count', width: '120px')
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->earner_username = $entry->earner_username ?: __m('N/A', 'RenCommissions');
        $entry->total_amount = ns()->currency->define((float) $entry->total_amount)->format();
        $entry->pending_amount = ns()->currency->define((float) $entry->pending_amount)->format();
        $entry->paid_amount = ns()->currency->define((float) $entry->paid_amount)->format();

        return $entry;
    }

    public function getEntries($config = []): array
    {
        $request = app(Request::class);
        $perPage = (int) ($request->query('per_page', $config['per_page'] ?? 20));
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $active = (string) $request->query('active', '');
        $direction = strtolower((string) $request->query('direction', 'desc'));
        $dailyDate = (string) $request->query('daily_date', '');
        $biweekly = (string) $request->query('biweekly', '');
        $scope = (string) $request->query('scope', 'global');
        $earnerFilter = (int) $request->query('earner_id', 0);

        $sortMap = [
            'earner_username' => 'earner_username',
            'total_amount' => 'total_amount',
            'pending_amount' => 'pending_amount',
            'paid_amount' => 'paid_amount',
            'rows_count' => 'rows_count',
        ];

        $query = DB::table('rencommissions_order_item_commissions as c')
            ->leftJoin('nexopos_users as earner', 'c.earner_id', '=', 'earner.id')
            ->selectRaw('MIN(c.id) as id')
            ->selectRaw('c.earner_id')
            ->selectRaw('COALESCE(earner.username, ?) as earner_username', [__m('N/A', 'RenCommissions')])
            ->selectRaw('SUM(c.total_commission) as total_amount')
            ->selectRaw("SUM(CASE WHEN c.status = 'pending' THEN c.total_commission ELSE 0 END) as pending_amount")
            ->selectRaw("SUM(CASE WHEN c.status = 'paid' THEN c.total_commission ELSE 0 END) as paid_amount")
            ->selectRaw('COUNT(*) as rows_count')
            ->groupBy('c.earner_id', 'earner.username');

        $storeId = StoreContext::id();
        if ($storeId !== null) {
            $query->where('c.store_id', $storeId);
        }

        if ($search !== '') {
            $query->where('earner.username', 'like', '%' . $search . '%');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dailyDate)) {
            $query->whereDate('c.created_at', $dailyDate);
            $anchor = Carbon::parse($dailyDate);
        } else {
            $anchor = now();
        }

        if ($biweekly === 'first_half') {
            $query->whereBetween('c.created_at', [
                $anchor->copy()->startOfMonth()->startOfDay(),
                $anchor->copy()->startOfMonth()->day(14)->endOfDay(),
            ]);
        } elseif ($biweekly === 'second_half') {
            $query->whereBetween('c.created_at', [
                $anchor->copy()->startOfMonth()->day(15)->startOfDay(),
                $anchor->copy()->endOfMonth()->endOfDay(),
            ]);
        }

        if ($scope === 'earner' && $earnerFilter > 0) {
            $query->where('c.earner_id', $earnerFilter);
        }

        if (isset($sortMap[$active]) && in_array($direction, ['asc', 'desc'], true)) {
            $query->orderBy($sortMap[$active], $direction);
        } else {
            $query->orderBy('total_amount', 'desc');
        }

        $result = $query->paginate($perPage, ['*'], 'page', $page)->toArray();
        $result['data'] = collect($result['data'])
            ->map(function ($row) {
                $entry = new CrudEntry((array) $row);

                return $this->setActions($entry);
            })
            ->values()
            ->toArray();

        return $result;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url('dashboard/rencommissions/staff-earnings'),
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
