<?php
/**
 * Voucher Template CRUD
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Crud;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Services\CrudService;
use App\Services\CrudEntry;
use Modules\GiftVouchers\Models\VoucherTemplate;
use Modules\GiftVouchers\Enums\TemplateStatus;

class VoucherTemplateCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'gift-vouchers.templates';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_gift_voucher_templates';

    /**
     * Define the namespace
     */
    protected $namespace = 'gift-vouchers.templates';

    /**
     * Model Used
     */
    protected $model = VoucherTemplate::class;

    /**
     * Define the main route
     */
    protected $mainRoute = 'ns.gift-vouchers.templates';

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => 'create.gift-voucher-templates',
        'read' => 'read.gift-voucher-templates',
        'update' => 'update.gift-voucher-templates',
        'delete' => 'delete.gift-voucher-templates',
    ];

    /**
     * Adding relations
     */
    public $relations = [
        ['nexopos_users as author', 'nexopos_gift_voucher_templates.author', '=', 'author.id'],
    ];

    /**
     * Pick fields from relations
     */
    public $pick = [
        'author' => ['username'],
    ];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [
        'name',
        'description',
        'validity_days',
        'is_transferable',
        'status',
    ];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the label used for the CRUD
     */
    public function getLabels(): array
    {
        return [
            'list_title' => __('Voucher Templates'),
            'list_description' => __('Manage gift voucher templates'),
            'no_entry' => __('No voucher templates found'),
            'create_new' => __('Create Template'),
            'create_title' => __('Create Voucher Template'),
            'create_description' => __('Create a new gift voucher template'),
            'edit_title' => __('Edit Voucher Template'),
            'edit_description' => __('Modify an existing voucher template'),
            'back_to_list' => __('Back to templates'),
        ];
    }

    /**
     * Get links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/gift-vouchers/templates'),
            'create' => ns()->url('dashboard/gift-vouchers/templates/create'),
            'edit' => ns()->url('dashboard/gift-vouchers/templates/edit/{id}'),
            'post' => ns()->url('api/crud/' . self::IDENTIFIER),
            'put' => ns()->url('api/crud/' . self::IDENTIFIER . '/{id}'),
        ];
    }

    /**
     * Get table configuration
     */
    public function getTable(): array
    {
        return [
            'name' => [
                'label' => __('Name'),
                '$direction' => '',
                '$sort' => true,
            ],
            'total_value' => [
                'label' => __('Total Value'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'validity_days' => [
                'label' => __('Validity (Days)'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'status' => [
                'label' => __('Status'),
                '$direction' => '',
                '$sort' => true,
                'width' => '100px',
            ],
            'author_username' => [
                'label' => __('Author'),
                '$direction' => '',
                '$sort' => false,
                'width' => '120px',
            ],
            'created_at' => [
                'label' => __('Created'),
                '$direction' => '',
                '$sort' => true,
                'width' => '150px',
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->action(
            label: __('Edit'),
            namespace: 'edit',
            type: 'GOTO',
            url: ns()->url('dashboard/gift-vouchers/templates/edit/' . $entry->id),
        );

        $entry->action(
            label: __('Delete'),
            namespace: 'delete',
            type: 'DELETE',
            url: ns()->url('api/crud/' . self::IDENTIFIER . '/' . $entry->id),
            confirm: [
                'message' => __('Are you sure you want to delete this template?'),
            ],
        );

        return $entry;
    }

    /**
     * Get bulk actions
     */
    public function getBulkActions(): array
    {
        return [
            [
                'label' => __('Delete Selected'),
                'identifier' => 'delete_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', ['namespace' => self::IDENTIFIER]),
                'confirm' => __('Are you sure you want to delete selected templates?'),
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
                validation: 'required|string|max:255',
                description: __('Provide a name for this voucher template.')
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: [
                        FormInput::textarea(
                            label: __('Description'),
                            name: 'description',
                            value: $entry->description ?? '',
                            description: __('Describe what this voucher template includes.')
                        ),
                        FormInput::number(
                            label: __('Validity Days'),
                            name: 'validity_days',
                            value: $entry->validity_days ?? 90,
                            validation: 'required|integer|min:1',
                            description: __('Number of days the voucher is valid after purchase.')
                        ),
                        FormInput::switch(
                            label: __('Transferable'),
                            name: 'is_transferable',
                            options: [
                                ['label' => __('Yes'), 'value' => 1],
                                ['label' => __('No'), 'value' => 0],
                            ],
                            value: $entry->is_transferable ?? 1,
                            description: __('Whether the voucher can be transferred to another person.')
                        ),
                        FormInput::select(
                            label: __('Status'),
                            name: 'status',
                            options: [
                                ['label' => __('Active'), 'value' => TemplateStatus::ACTIVE->value],
                                ['label' => __('Inactive'), 'value' => TemplateStatus::INACTIVE->value],
                            ],
                            value: $entry->status ?? TemplateStatus::ACTIVE->value,
                            description: __('Set the template availability status.')
                        ),
                    ]
                )
            )
        );
    }

    /**
     * Before delete hook
     */
    public function beforeDelete($namespace, $id, $model)
    {
        if ($model->vouchers()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cannot delete template that has issued vouchers.'),
            ], 403);
        }
    }

    /**
     * After post hook
     */
    public function afterPost($inputs, $entry)
    {
        // Template items are managed separately via the API
        return $inputs;
    }
}
