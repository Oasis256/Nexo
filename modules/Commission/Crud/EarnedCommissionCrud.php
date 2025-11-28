<?php

namespace Modules\Commission\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\EarnedCommission;
use TorMorten\Eventy\Facades\Events as Hook;

class EarnedCommissionCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the CRUD identifier
     */
    const IDENTIFIER = 'commission.earned';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_earned_commissions';

    /**
     * Default slug
     */
    protected $slug = 'commissions/earned';

    /**
     * Define namespace
     */
    protected $namespace = 'commission.earned';

    /**
     * Model Used
     */
    protected $model = EarnedCommission::class;

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => false, // No manual creation
        'read' => 'commission.earnings.read',
        'update' => false, // No manual updates
        'delete' => 'commission.earnings.delete',
    ];

    /**
     * Adding relations
     */
    public $relations = [
        ['nexopos_orders as order', 'order.id', '=', 'nexopos_earned_commissions.order_id'],
        ['nexopos_users as user', 'user.id', '=', 'nexopos_earned_commissions.user_id'],
        ['nexopos_commissions as commission', 'commission.id', '=', 'nexopos_earned_commissions.commission_id'],
        ['nexopos_products as product', 'product.id', '=', 'nexopos_earned_commissions.product_id'],
    ];

    /**
     * Pick columns from relations
     */
    public $pick = [
        'order' => ['code'],
        'user' => ['username'],
        'commission' => ['name'],
        'product' => ['name'],
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
     * Return the label used for the CRUD instance
     */
    public function getLabels(): array
    {
        return [
            'list_title' => __m('Earned Commissions', 'Commission'),
            'list_description' => __m('Display all earned commission records.', 'Commission'),
            'no_entry' => __m('No earned commissions have been recorded', 'Commission'),
            'create_new' => false,
            'create_title' => false,
            'create_description' => false,
            'edit_title' => __m('View Earned Commission', 'Commission'),
            'edit_description' => __m('View earned commission details.', 'Commission'),
            'back_to_list' => __m('Return to Earned Commissions', 'Commission'),
        ];
    }

    /**
     * Check whether a feature is enabled
     */
    public function isEnabled($feature): bool
    {
        return false;
    }

    /**
     * Get form configuration (read-only view)
     */
    public function getForm($entry = null): array
    {
        return [
            'main' => [
                'label' => __m('Commission Name', 'Commission'),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'disabled' => true,
            ],
            'tabs' => [
                'general' => [
                    'label' => __m('Details', 'Commission'),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'value',
                            'label' => __m('Commission Value', 'Commission'),
                            'value' => $entry ? ns()->currency->define($entry->value)->format() : '',
                            'disabled' => true,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'commission_type',
                            'label' => __m('Commission Type', 'Commission'),
                            'value' => $entry->commission_type ?? '',
                            'disabled' => true,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'quantity',
                            'label' => __m('Quantity', 'Commission'),
                            'value' => $entry->quantity ?? '',
                            'disabled' => true,
                        ],
                        [
                            'type' => 'text',
                            'name' => 'base_amount',
                            'label' => __m('Base Amount', 'Commission'),
                            'value' => $entry ? ns()->currency->define($entry->base_amount)->format() : '',
                            'disabled' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return [
            'name' => [
                'label' => __m('Commission', 'Commission'),
                '$direction' => '',
                '$sort' => true,
            ],
            'user_username' => [
                'label' => __m('Earner', 'Commission'),
                '$direction' => '',
                '$sort' => true,
            ],
            'order_code' => [
                'label' => __m('Order', 'Commission'),
                '$direction' => '',
                '$sort' => true,
            ],
            'product_name' => [
                'label' => __m('Product', 'Commission'),
                '$direction' => '',
                '$sort' => false,
            ],
            'commission_type' => [
                'label' => __m('Type', 'Commission'),
                '$direction' => '',
                '$sort' => true,
            ],
            'value' => [
                'label' => __m('Value', 'Commission'),
                '$direction' => '',
                '$sort' => true,
            ],
            'created_at' => [
                'label' => __m('Date', 'Commission'),
                '$direction' => 'desc',
                '$sort' => true,
            ],
        ];
    }

    /**
     * Define actions
     */
    protected function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->{'$checked'} = false;
        $entry->{'$toggled'} = false;
        $entry->{'$id'} = $entry->id;

        // Format type display
        $typeLabels = [
            Commission::TYPE_ON_THE_HOUSE => __m('On The House', 'Commission'),
            Commission::TYPE_FIXED => __m('Fixed', 'Commission'),
            Commission::TYPE_PERCENTAGE => __m('Percentage', 'Commission'),
        ];
        $entry->commission_type = $typeLabels[$entry->commission_type] ?? $entry->commission_type;

        // Format value
        $entry->value = ns()->currency->define($entry->value)->format();

        // View order action
        $entry->action(
            label: __m('View Order', 'Commission'),
            identifier: 'view_order',
            url: ns()->url('/dashboard/orders/invoice/' . $entry->order_id),
            type: 'GOTO'
        );

        $entry->action(
            label: __m('Delete', 'Commission'),
            identifier: 'delete',
            url: ns()->url('/api/crud/commission.earned/' . $entry->id),
            confirm: [
                'message' => __m('Would you like to delete this earned commission record?', 'Commission'),
            ],
            type: 'DELETE'
        );

        return $entry;
    }

    /**
     * Before Delete
     */
    public function beforeDelete($namespace, $id, $model): void
    {
        if ($this->permissions['delete'] !== false) {
            ns()->restrict($this->permissions['delete']);
        } else {
            throw new NotAllowedException;
        }
    }

    /**
     * Bulk Delete Action
     */
    public function bulkAction(Request $request): array
    {
        if ($request->input('action') === 'delete_selected') {
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
            } else {
                throw new NotAllowedException;
            }

            $status = ['success' => 0, 'failed' => 0];

            foreach ($request->input('entries') as $id) {
                $entity = $this->model::find($id);
                if ($entity instanceof EarnedCommission) {
                    $entity->delete();
                    $status['success']++;
                } else {
                    $status['failed']++;
                }
            }

            return $status;
        }

        return [];
    }

    /**
     * Get Links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/commissions/earned'),
            'create' => false,
            'edit' => ns()->url('dashboard/commissions/earned/edit/'),
            'post' => false,
            'put' => false,
        ];
    }

    /**
     * Get Bulk actions
     */
    public function getBulkActions(): array
    {
        return [
            [
                'label' => __m('Delete Selected', 'Commission'),
                'identifier' => 'delete_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ];
    }

    /**
     * Query filters
     */
    public function getQueryFilters(): array
    {
        return [
            [
                'type' => 'daterangepicker',
                'name' => 'date_range',
                'label' => __m('Date Range', 'Commission'),
            ],
            [
                'type' => 'select',
                'name' => 'user_id',
                'label' => __m('User', 'Commission'),
                'options' => Helper::toJsOptions(User::get(), ['id', 'username']),
            ],
            [
                'type' => 'select',
                'name' => 'commission_type',
                'label' => __m('Type', 'Commission'),
                'options' => Helper::kvToJsOptions([
                    '' => __m('All Types', 'Commission'),
                    Commission::TYPE_ON_THE_HOUSE => __m('On The House', 'Commission'),
                    Commission::TYPE_FIXED => __m('Fixed', 'Commission'),
                    Commission::TYPE_PERCENTAGE => __m('Percentage', 'Commission'),
                ]),
            ],
        ];
    }

    /**
     * Hook for query modifications
     */
    public function hook($query): void
    {
        $query->orderBy('created_at', 'desc');
    }

    /**
     * Get exports
     */
    public function getExports(): array
    {
        return [
            [
                'label' => __m('Export CSV', 'Commission'),
                'identifier' => 'export_csv',
                'url' => ns()->route('commission.export.csv'),
            ],
        ];
    }
}
