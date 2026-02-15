<?php
/**
 * Voucher CRUD
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Crud;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Services\CrudService;
use App\Services\CrudEntry;
use Modules\GiftVouchers\Models\Voucher;
use Modules\GiftVouchers\Enums\VoucherStatus;

class VoucherCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'gift-vouchers.vouchers';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_gift_vouchers';

    /**
     * Define the namespace
     */
    protected $namespace = 'gift-vouchers.vouchers';

    /**
     * Model Used
     */
    protected $model = Voucher::class;

    /**
     * Define the main route
     */
    protected $mainRoute = 'ns.gift-vouchers';

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => 'create.gift-vouchers',
        'read' => 'read.gift-vouchers',
        'update' => 'update.gift-vouchers',
        'delete' => 'delete.gift-vouchers',
    ];

    /**
     * Adding relations
     */
    public $relations = [
        ['nexopos_users as author', 'nexopos_gift_vouchers.author', '=', 'author.id'],
        ['nexopos_gift_voucher_templates as template', 'nexopos_gift_vouchers.template_id', '=', 'template.id'],
        ['nexopos_users as purchaser', 'nexopos_gift_vouchers.purchaser_id', '=', 'purchaser.id'],
    ];

    /**
     * Pick fields from relations
     */
    public $pick = [
        'author' => ['username'],
        'template' => ['name'],
        'purchaser' => ['first_name', 'last_name'],
    ];

    /**
     * Define casts
     */
    protected $casts = [
        'total_value' => CurrencyCast::class,
        'remaining_value' => CurrencyCast::class,
        'expires_at' => DateCast::class,
        'created_at' => DateCast::class,
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
            'list_title' => __('Gift Vouchers'),
            'list_description' => __('Manage gift vouchers'),
            'no_entry' => __('No vouchers found'),
            'create_new' => __('Create Voucher'),
            'create_title' => __('Create Gift Voucher'),
            'create_description' => __('Issue a new gift voucher'),
            'edit_title' => __('Edit Gift Voucher'),
            'edit_description' => __('Modify an existing voucher'),
            'back_to_list' => __('Back to vouchers'),
        ];
    }

    /**
     * Get links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/gift-vouchers'),
            'create' => ns()->url('dashboard/gift-vouchers/create'),
            'edit' => ns()->url('dashboard/gift-vouchers/edit/{id}'),
            'post' => ns()->url('api/gift-vouchers'),
            'put' => ns()->url('api/gift-vouchers/{id}'),
        ];
    }

    /**
     * Get table configuration
     */
    public function getTable(): array
    {
        return [
            'code' => [
                'label' => __('Code'),
                '$direction' => '',
                '$sort' => true,
                'width' => '140px',
            ],
            'template_name' => [
                'label' => __('Template'),
                '$direction' => '',
                '$sort' => false,
            ],
            'purchaser_first_name' => [
                'label' => __('Purchaser'),
                '$direction' => '',
                '$sort' => false,
            ],
            'total_value' => [
                'label' => __('Total Value'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'remaining_value' => [
                'label' => __('Remaining'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'status' => [
                'label' => __('Status'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'expires_at' => [
                'label' => __('Expires'),
                '$direction' => '',
                '$sort' => true,
                'width' => '150px',
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
            label: __('View'),
            namespace: 'view',
            type: 'GOTO',
            url: ns()->url('dashboard/gift-vouchers/view/' . $entry->id),
        );

        $entry->action(
            label: __('Edit'),
            namespace: 'edit',
            type: 'GOTO',
            url: ns()->url('dashboard/gift-vouchers/edit/' . $entry->id),
        );

        // Add status-specific styling
        $status = $entry->status;
        $statusClass = match($status) {
            VoucherStatus::ACTIVE->value => 'bg-green-100 text-green-800',
            VoucherStatus::PARTIALLY_REDEEMED->value => 'bg-blue-100 text-blue-800',
            VoucherStatus::FULLY_REDEEMED->value => 'bg-gray-100 text-gray-800',
            VoucherStatus::EXPIRED->value => 'bg-yellow-100 text-yellow-800',
            VoucherStatus::CANCELLED->value => 'bg-red-100 text-red-800',
            default => '',
        };

        if ($statusClass) {
            $entry->addClass($statusClass);
        }

        return $entry;
    }

    /**
     * Get bulk actions
     */
    public function getBulkActions(): array
    {
        return [];
    }

    /**
     * Get form configuration
     */
    public function getForm($entry = null): array
    {
        return CrudForm::form(
            main: FormInput::searchSelect(
                label: __('Template'),
                name: 'template_id',
                value: $entry->template_id ?? '',
                validation: 'required',
                description: __('Select a voucher template.'),
                component: 'nsCrudForm',
                props: [
                    'src' => ns()->url('api/crud/gift-vouchers.templates/form-config'),
                ],
                disabled: $entry !== null,
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: [
                        FormInput::searchSelect(
                            label: __('Purchaser'),
                            name: 'purchaser_id',
                            value: $entry->purchaser_id ?? '',
                            description: __('Select the customer purchasing this voucher.'),
                            component: 'nsCrudForm',
                            props: [
                                'src' => ns()->url('api/crud/ns.customers/form-config'),
                            ],
                        ),
                        FormInput::datetime(
                            label: __('Expires At'),
                            name: 'expires_at',
                            value: $entry->expires_at ?? '',
                            description: __('When the voucher expires (leave empty for default).'),
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
        if ($model->redemptions()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cannot delete voucher that has been redeemed.'),
            ], 403);
        }
    }
}
