<?php

namespace Modules\WhatsApp\Crud;

use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Modules\WhatsApp\Enums\MessageStatus;
use Modules\WhatsApp\Enums\MessageType;
use Modules\WhatsApp\Enums\RecipientType;
use Modules\WhatsApp\Models\MessageLog;

class MessageLogCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'whatsapp.logs';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_whatsapp_logs';

    /**
     * Define the namespace
     */
    protected $namespace = 'whatsapp.logs';

    /**
     * Model Used
     */
    protected $model = MessageLog::class;

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => false,
        'read' => 'whatsapp.logs.read',
        'update' => false,
        'delete' => 'whatsapp.logs.delete',
    ];

    /**
     * Adding relations
     */
    public $relations = [
        ['nexopos_whatsapp_templates as template', 'nexopos_whatsapp_logs.template_id', '=', 'template.id'],
    ];

    /**
     * Pick fields from relations
     */
    public $pick = [
        'template' => ['label'],
    ];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the table labels
     */
    public function getLabels(): array
    {
        return [
            'list_title' => __('WhatsApp Message Logs'),
            'list_description' => __('View all sent WhatsApp messages and their delivery status.'),
            'no_entry' => __('No messages found.'),
        ];
    }

    /**
     * Check if crud feature is enabled
     */
    public function isEnabled($feature): bool
    {
        return false; // Uses external pages
    }

    /**
     * Fields to show as filters
     */
    public function getFilterable(): array
    {
        return [
            'status' => [
                'label' => __('Status'),
                'options' => $this->getStatusOptions(),
            ],
            'message_type' => [
                'label' => __('Type'),
                'options' => $this->getTypeOptions(),
            ],
            'recipient_type' => [
                'label' => __('Recipient'),
                'options' => $this->getRecipientOptions(),
            ],
        ];
    }

    /**
     * Get status options
     */
    protected function getStatusOptions(): array
    {
        return [
            ['label' => __('All'), 'value' => ''],
            ['label' => __('Pending'), 'value' => MessageStatus::PENDING->value],
            ['label' => __('Sent'), 'value' => MessageStatus::SENT->value],
            ['label' => __('Delivered'), 'value' => MessageStatus::DELIVERED->value],
            ['label' => __('Read'), 'value' => MessageStatus::READ->value],
            ['label' => __('Failed'), 'value' => MessageStatus::FAILED->value],
        ];
    }

    /**
     * Get type options
     */
    protected function getTypeOptions(): array
    {
        return [
            ['label' => __('All'), 'value' => ''],
            ['label' => __('Text'), 'value' => MessageType::TEXT->value],
            ['label' => __('Template'), 'value' => MessageType::TEMPLATE->value],
            ['label' => __('Image'), 'value' => MessageType::IMAGE->value],
            ['label' => __('Document'), 'value' => MessageType::DOCUMENT->value],
        ];
    }

    /**
     * Get recipient options
     */
    protected function getRecipientOptions(): array
    {
        return [
            ['label' => __('All'), 'value' => ''],
            ['label' => __('Customer'), 'value' => RecipientType::CUSTOMER->value],
            ['label' => __('User'), 'value' => RecipientType::USER->value],
        ];
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                label: __('Recipient'),
                identifier: 'recipient_phone',
            ),
            CrudTable::column(
                label: __('Template'),
                identifier: 'template_label',
                sort: false,
            ),
            CrudTable::column(
                label: __('Type'),
                identifier: 'message_type',
                sort: false,
                width: '100px',
            ),
            CrudTable::column(
                label: __('Status'),
                identifier: 'status',
                width: '100px',
            ),
            CrudTable::column(
                label: __('Sent'),
                identifier: 'sent_at',
                width: '160px',
                direction: 'desc',
            ),
        );
    }

    /**
     * Define links for actions
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/whatsapp/logs'),
        ];
    }

    /**
     * Get actions configuration
     */
    public function setActions(CrudEntry $entry): CrudEntry
    {
        // Store raw values before formatting
        $rawStatus = $entry->getRawValue('status');
        $rawMessageType = $entry->getRawValue('message_type');
        $recipientName = $entry->getRawValue('recipient_name') ?? '';
        $recipientPhone = $entry->getRawValue('recipient_phone') ?? '';

        // Format recipient
        $entry->recipient_phone = $recipientName 
            ? "{$recipientName}<br><small class=\"text-secondary\">{$recipientPhone}</small>"
            : $recipientPhone;

        // Format status with colors
        $statusColors = [
            MessageStatus::PENDING->value => 'text-warning-tertiary',
            MessageStatus::SENT->value => 'text-info-tertiary',
            MessageStatus::DELIVERED->value => 'text-success-tertiary',
            MessageStatus::READ->value => 'text-success-primary',
            MessageStatus::FAILED->value => 'text-error-tertiary',
        ];
        $statusLabels = [
            MessageStatus::PENDING->value => __('Pending'),
            MessageStatus::SENT->value => __('Sent'),
            MessageStatus::DELIVERED->value => __('Delivered'),
            MessageStatus::READ->value => __('Read'),
            MessageStatus::FAILED->value => __('Failed'),
        ];
        $colorClass = $statusColors[$rawStatus] ?? '';
        $statusLabel = $statusLabels[$rawStatus] ?? $rawStatus;
        $entry->status = "<span class=\"{$colorClass}\">{$statusLabel}</span>";

        // Format message type
        $typeLabels = [
            MessageType::TEXT->value => __('Text'),
            MessageType::TEMPLATE->value => __('Template'),
            MessageType::IMAGE->value => __('Image'),
            MessageType::DOCUMENT->value => __('Document'),
            MessageType::INTERACTIVE->value => __('Interactive'),
        ];
        $entry->message_type = $typeLabels[$rawMessageType] ?? $rawMessageType;

        // Format sent_at
        if ($entry->sent_at) {
            $entry->sent_at = ns()->date->getFormatted($entry->sent_at);
        } else {
            $entry->sent_at = '-';
        }

        // Add actions
        $entry->action(
            identifier: 'view',
            label: __('View'),
            type: 'GOTO',
            url: ns()->url('dashboard/whatsapp/logs/' . $entry->id),
        );

        if ($rawStatus === MessageStatus::FAILED->value) {
            $entry->action(
                identifier: 'retry',
                label: __('Retry'),
                type: 'POST',
                url: ns()->url('/api/whatsapp/logs/' . $entry->id . '/retry'),
                confirm: [
                    'message' => __('Retry sending this message?'),
                ]
            );
        }

        $entry->action(
            identifier: 'delete',
            label: __('Delete'),
            type: 'DELETE',
            url: ns()->url('/api/crud/whatsapp.logs/' . $entry->id),
            confirm: [
                'message' => __('Are you sure you want to delete this log entry?'),
            ]
        );

        return $entry;
    }

    /**
     * Get bulk actions configuration
     */
    public function getBulkActions(): array
    {
        return [
            [
                'label' => __('Delete Selected'),
                'identifier' => 'delete_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
            [
                'label' => __('Retry Failed'),
                'identifier' => 'retry_failed',
                'url' => ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ];
    }

    /**
     * Get form - read only display
     */
    public function getForm($entry = null): array
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __('Message ID'),
                name: 'whatsapp_message_id',
                value: $entry->whatsapp_message_id ?? '',
                disabled: true
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'details',
                    label: __('Details'),
                    fields: [
                        FormInput::text(
                            label: __('Recipient'),
                            name: 'recipient_phone',
                            value: $entry->recipient_phone ?? '',
                            disabled: true
                        ),
                        FormInput::text(
                            label: __('Status'),
                            name: 'status',
                            value: $entry->status ?? '',
                            disabled: true
                        ),
                        FormInput::textarea(
                            label: __('Content'),
                            name: 'content',
                            value: $entry->content ?? '',
                            disabled: true
                        ),
                        FormInput::textarea(
                            label: __('Error'),
                            name: 'error_message',
                            value: $entry->error_message ?? '',
                            disabled: true
                        ),
                    ]
                )
            )
        );
    }

    /**
     * Modify query for default sorting
     */
    public function hook($query): void
    {
        $query->orderBy('created_at', 'desc');
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $entries = $request->input('entries', []);

        if (empty($entries)) {
            return response()->json([
                'status' => 'error',
                'message' => __('No entries selected.'),
            ], 400);
        }

        switch ($action) {
            case 'delete_selected':
                return $this->bulkDelete($entries);
            case 'retry_failed':
                return $this->bulkRetry($entries);
            default:
                return response()->json([
                    'status' => 'error',
                    'message' => __('Unknown action.'),
                ], 400);
        }
    }

    /**
     * Bulk delete logs
     */
    protected function bulkDelete(array $entries): array
    {
        $deleted = 0;

        foreach ($entries as $entry) {
            $log = MessageLog::find($entry['id']);
            if ($log) {
                $log->delete();
                $deleted++;
            }
        }

        return [
            'status' => 'success',
            'message' => sprintf(__('%d log(s) deleted successfully.'), $deleted),
        ];
    }

    /**
     * Bulk retry failed messages
     */
    protected function bulkRetry(array $entries): array
    {
        $retried = 0;
        $whatsAppService = app(\Modules\WhatsApp\Services\WhatsAppService::class);

        foreach ($entries as $entry) {
            $log = MessageLog::find($entry['id']);
            if ($log && $log->status === MessageStatus::FAILED->value) {
                try {
                    $whatsAppService->sendTextMessage(
                        phone: $log->recipient_phone,
                        message: $log->content,
                        recipientType: RecipientType::from($log->recipient_type),
                        recipientId: $log->recipient_id,
                        recipientName: $log->recipient_name,
                        relatedType: $log->related_type,
                        relatedId: $log->related_id
                    );
                    $retried++;
                } catch (\Exception $e) {
                    // Continue with other entries
                }
            }
        }

        return [
            'status' => 'success',
            'message' => sprintf(__('%d message(s) queued for retry.'), $retried),
        ];
    }
}
