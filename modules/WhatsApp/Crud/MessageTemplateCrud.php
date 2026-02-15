<?php

namespace Modules\WhatsApp\Crud;

use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use Modules\WhatsApp\Enums\TemplateTarget;
use Modules\WhatsApp\Models\MessageTemplate;

class MessageTemplateCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'whatsapp.templates';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_whatsapp_templates';

    /**
     * Define the namespace
     */
    protected $namespace = 'whatsapp.templates';

    /**
     * Model Used
     */
    protected $model = MessageTemplate::class;

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => 'whatsapp.templates.create',
        'read' => 'whatsapp.templates.read',
        'update' => 'whatsapp.templates.update',
        'delete' => 'whatsapp.templates.delete',
    ];

    /**
     * Adding relations
     */
    public $relations = [];

    /**
     * Pick fields from relations
     */
    public $pick = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [
        'name',
        'label',
        'event',
        'content',
        'is_active',
        'target',
        'meta',
    ];

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
            'list_title' => __('WhatsApp Message Templates'),
            'list_description' => __('Manage message templates for automated notifications.'),
            'create_title' => __('Create Template'),
            'create_description' => __('Create a new message template.'),
            'edit_title' => __('Edit Template'),
            'edit_description' => __('Modify an existing message template.'),
            'no_entry' => __('No templates found.'),
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
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                label: __('Name'),
                identifier: 'name',
            ),
            CrudTable::column(
                label: __('Label'),
                identifier: 'label',
            ),
            CrudTable::column(
                label: __('Event'),
                identifier: 'event',
            ),
            CrudTable::column(
                label: __('Target'),
                identifier: 'target',
                sort: false,
                width: '120px',
            ),
            CrudTable::column(
                label: __('Active'),
                identifier: 'is_active',
                sort: false,
                width: '80px',
            ),
            CrudTable::column(
                label: __('Created'),
                identifier: 'created_at',
                width: '150px',
            ),
        );
    }

    /**
     * Define links for actions
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/whatsapp/templates'),
            'create' => ns()->url('dashboard/whatsapp/templates/create'),
            'edit' => ns()->url('dashboard/whatsapp/templates/edit/{id}'),
        ];
    }

    /**
     * Get actions configuration
     */
    public function setActions(CrudEntry $entry): CrudEntry
    {
        // Store raw values before formatting
        $rawTarget = $entry->getRawValue('target');
        $rawName = $entry->getRawValue('name');
        $rawIsActive = $entry->getRawValue('is_active');

        // Format is_active
        $entry->is_active = $rawIsActive 
            ? '<span class="text-success-tertiary">' . __('Yes') . '</span>'
            : '<span class="text-error-tertiary">' . __('No') . '</span>';

        // Format target
        $targetLabels = [
            TemplateTarget::CUSTOMER->value => __('Customer'),
            TemplateTarget::STAFF->value => __('Staff'),
            TemplateTarget::BOTH->value => __('Both'),
        ];
        $entry->target = $targetLabels[$rawTarget] ?? $rawTarget;

        // Add actions
        $entry->action(
            identifier: 'edit',
            label: __('Edit'),
            type: 'GOTO',
            url: ns()->url('dashboard/whatsapp/templates/edit/' . $entry->id),
        );

        $entry->action(
            identifier: 'preview',
            label: __('Preview'),
            type: 'GOTO',
            url: ns()->url('dashboard/whatsapp/templates/' . $entry->id . '/preview'),
        );

        // Only allow delete for non-system templates
        $name = $rawName ?? '';
        if ($name !== 'order_confirmation' && $name !== 'payment_received') {
            $entry->action(
                identifier: 'delete',
                label: __('Delete'),
                type: 'DELETE',
                url: ns()->url('/api/crud/whatsapp.templates/' . $entry->id),
                confirm: [
                    'message' => __('Are you sure you want to delete this template?'),
                ]
            );
        }

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
                'label' => __('Enable Selected'),
                'identifier' => 'enable_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
            [
                'label' => __('Disable Selected'),
                'identifier' => 'disable_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ];
    }

    /**
     * Get form configuration
     */
    public function getForm($entry = null): array
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __('Template Name'),
                name: 'name',
                value: $entry->name ?? '',
                validation: 'required|string|max:100',
                description: __('Unique identifier for the template (e.g., order_confirmation).')
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: [
                        FormInput::text(
                            label: __('Label'),
                            name: 'label',
                            value: $entry->label ?? '',
                            validation: 'required|string|max:255',
                            description: __('Human-readable name for the template.')
                        ),
                        FormInput::select(
                            label: __('Event'),
                            name: 'event',
                            value: $entry->event ?? '',
                            options: $this->getEventOptions(),
                            description: __('The event that triggers this template.')
                        ),
                        FormInput::select(
                            label: __('Target'),
                            name: 'target',
                            value: $entry->target ?? TemplateTarget::CUSTOMER->value,
                            options: [
                                ['label' => __('Customer'), 'value' => TemplateTarget::CUSTOMER->value],
                                ['label' => __('Staff'), 'value' => TemplateTarget::STAFF->value],
                                ['label' => __('Both'), 'value' => TemplateTarget::BOTH->value],
                            ],
                            validation: 'required',
                            description: __('Who should receive messages from this template.')
                        ),
                        FormInput::switch(
                            label: __('Active'),
                            name: 'is_active',
                            value: $entry->is_active ?? true,
                            options: [
                                ['label' => __('Yes'), 'value' => true],
                                ['label' => __('No'), 'value' => false],
                            ],
                            description: __('Enable or disable this template.')
                        ),
                    ]
                ),
                CrudForm::tab(
                    identifier: 'content',
                    label: __('Content'),
                    fields: [
                        FormInput::textarea(
                            label: __('Message Content'),
                            name: 'content',
                            value: $entry->content ?? '',
                            validation: 'required|string|max:4096',
                            description: $this->getPlaceholderHelp()
                        ),
                    ]
                )
            )
        );
    }

    /**
     * Get event options for select
     */
    protected function getEventOptions(): array
    {
        $events = MessageTemplate::getAvailableEvents();
        $options = [
            ['label' => __('Select an event'), 'value' => ''],
        ];

        foreach ($events as $event => $label) {
            $options[] = ['label' => $label, 'value' => $event];
        }

        return $options;
    }

    /**
     * Get placeholder help text
     */
    protected function getPlaceholderHelp(): string
    {
        return __('Available placeholders: {customer_name}, {customer_phone}, {order_id}, {order_total}, {order_status}, {payment_method}, {store_name}, {product_list}, {date}, {time}');
    }

    /**
     * Before creating
     */
    public function beforePost($inputs)
    {
        // Sanitize name
        $inputs['name'] = strtolower(str_replace([' ', '-'], '_', $inputs['name']));

        return $inputs;
    }

    /**
     * Before updating
     */
    public function beforePut($inputs, $entry)
    {
        // Sanitize name
        $inputs['name'] = strtolower(str_replace([' ', '-'], '_', $inputs['name']));

        return $inputs;
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
            case 'enable_selected':
                return $this->bulkUpdateStatus($entries, true);
            case 'disable_selected':
                return $this->bulkUpdateStatus($entries, false);
            default:
                return response()->json([
                    'status' => 'error',
                    'message' => __('Unknown action.'),
                ], 400);
        }
    }

    /**
     * Bulk delete templates
     */
    protected function bulkDelete(array $entries): array
    {
        $deleted = 0;

        foreach ($entries as $entry) {
            $template = MessageTemplate::find($entry['id']);
            if ($template) {
                $template->delete();
                $deleted++;
            }
        }

        return [
            'status' => 'success',
            'message' => sprintf(__('%d template(s) deleted successfully.'), $deleted),
        ];
    }

    /**
     * Bulk update status
     */
    protected function bulkUpdateStatus(array $entries, bool $status): array
    {
        $updated = 0;

        foreach ($entries as $entry) {
            $template = MessageTemplate::find($entry['id']);
            if ($template) {
                $template->is_active = $status;
                $template->save();
                $updated++;
            }
        }

        $action = $status ? __('enabled') : __('disabled');

        return [
            'status' => 'success',
            'message' => sprintf(__('%d template(s) %s successfully.'), $updated, $action),
        ];
    }
}
