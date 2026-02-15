<?php

namespace Modules\BookingVisitors\Crud;

use App\Classes\CrudTable;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Modules\BookingVisitors\Models\AuditLog;
use Modules\BookingVisitors\Support\StoreContext;

class AuditLogsCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'bookingvisitors.audit-logs';

    protected $table = 'bookingvisitors_audit_logs';

    protected $model = AuditLog::class;

    protected $namespace = self::IDENTIFIER;

    protected $slug = 'bookingvisitors/audit-logs';

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.bookingvisitors.reports.read',
        'update' => false,
        'delete' => false,
    ];

    protected $showOptions = false;

    public function __construct()
    {
        parent::__construct();
    }

    public function hook($query): void
    {
        StoreContext::apply($query, 'bookingvisitors_audit_logs.store_id');
    }

    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __m('Audit Logs', 'BookingVisitors'),
            list_description: __m('Security and operation logs for booking and visitor events.', 'BookingVisitors'),
            no_entry: __m('No audit logs found.', 'BookingVisitors'),
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
            CrudTable::column(label: __m('Action', 'BookingVisitors'), identifier: 'action', width: '220px'),
            CrudTable::column(label: __m('Entity', 'BookingVisitors'), identifier: 'entity_type', width: '140px'),
            CrudTable::column(label: __m('Entity ID', 'BookingVisitors'), identifier: 'entity_id', width: '100px'),
            CrudTable::column(label: __m('Author', 'BookingVisitors'), identifier: 'author', width: '90px'),
            CrudTable::column(label: __m('Date', 'BookingVisitors'), identifier: 'created_at', width: '170px')
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->entity_type = strtoupper((string) $entry->entity_type);

        return $entry;
    }

    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->route('bookingvisitors.logs'),
            create: '',
            edit: '',
            post: '',
            put: ''
        );
    }
}

