<?php

namespace Modules\Commission\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\CommissionCategory;
use TorMorten\Eventy\Facades\Events as Hook;

class CommissionCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the CRUD identifier
     */
    const IDENTIFIER = 'commission.commissions';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_commissions';

    /**
     * Default slug
     */
    protected $slug = 'commissions';

    /**
     * Define namespace
     */
    protected $namespace = 'commission.commissions';

    /**
     * Model Used
     */
    protected $model = Commission::class;

    /**
     * Define permissions
     */
    protected $permissions = [
        'create' => 'commission.create',
        'read' => 'commission.read',
        'update' => 'commission.update',
        'delete' => 'commission.delete',
    ];

    /**
     * Adding relations
     */
    public $relations = [
        ['nexopos_users as user', 'user.id', '=', 'nexopos_commissions.author'],
        ['nexopos_roles as role', 'role.id', '=', 'nexopos_commissions.role_id'],
    ];

    /**
     * Pick columns from relations
     */
    public $pick = [
        'user' => ['username'],
        'role' => ['name'],
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
            'list_title' => __m('Commissions List', 'Commission'),
            'list_description' => __m('Display all commission definitions.', 'Commission'),
            'no_entry' => __m('No commissions have been registered', 'Commission'),
            'create_new' => __m('Add a new commission', 'Commission'),
            'create_title' => __m('Create a new commission', 'Commission'),
            'create_description' => __m('Register a new commission rate and save it.', 'Commission'),
            'edit_title' => __m('Edit commission', 'Commission'),
            'edit_description' => __m('Modify commission settings.', 'Commission'),
            'back_to_list' => __m('Return to Commissions', 'Commission'),
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
     * Get form configuration
     */
    public function getForm($entry = null): array
    {
        return [
            'main' => [
                'label' => __m('Name', 'Commission'),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'description' => __m('Provide a name for this commission.', 'Commission'),
                'validation' => 'required|string|max:255',
            ],
            'tabs' => [
                'general' => [
                    'label' => __m('General', 'Commission'),
                    'fields' => [
                        [
                            'type' => 'switch',
                            'name' => 'active',
                            'options' => Helper::kvToJsOptions([
                                0 => __m('No', 'Commission'),
                                1 => __m('Yes', 'Commission'),
                            ]),
                            'description' => __m('Define whether the commission is active or not.', 'Commission'),
                            'label' => __m('Active', 'Commission'),
                            'value' => $entry->active ?? 1,
                        ],
                        [
                            'type' => 'select',
                            'name' => 'type',
                            'options' => Helper::kvToJsOptions([
                                Commission::TYPE_ON_THE_HOUSE => __m('On The House', 'Commission'),
                                Commission::TYPE_FIXED => __m('Fixed', 'Commission'),
                                Commission::TYPE_PERCENTAGE => __m('Percentage', 'Commission'),
                            ]),
                            'label' => __m('Type', 'Commission'),
                            'description' => __m('On The House: Fixed value regardless of discounts/taxes. Fixed: Individual commission per item. Percentage: Flat percentage for all items.', 'Commission'),
                            'value' => $entry->type ?? Commission::TYPE_PERCENTAGE,
                            'validation' => 'required',
                        ],
                        [
                            'type' => 'text',
                            'name' => 'value',
                            'label' => __m('Default Value', 'Commission'),
                            'description' => __m('Default commission value. For Fixed type, per-product values can override this.', 'Commission'),
                            'value' => $entry->value ?? 0,
                            'validation' => 'required|numeric|min:0',
                        ],
                        [
                            'type' => 'select',
                            'name' => 'calculation_base',
                            'options' => Helper::kvToJsOptions([
                                Commission::BASE_GROSS => __m('Gross Amount (before discount)', 'Commission'),
                                Commission::BASE_NET => __m('Net Amount (after discount)', 'Commission'),
                                Commission::BASE_FIXED => __m('Fixed (unit price)', 'Commission'),
                            ]),
                            'label' => __m('Calculation Base', 'Commission'),
                            'description' => __m('For percentage type, determines what base amount to calculate from.', 'Commission'),
                            'value' => $entry->calculation_base ?? Commission::BASE_GROSS,
                            'show' => function ($fields) {
                                return ($fields['type'] ?? '') === Commission::TYPE_PERCENTAGE;
                            },
                        ],
                        [
                            'type' => 'multiselect',
                            'options' => Helper::toJsOptions(ProductCategory::get(), ['id', 'name']),
                            'name' => 'categories',
                            'label' => __m('Assigned Categories', 'Commission'),
                            'description' => __m('The commission will be effective for products in selected categories. Leave empty for all products.', 'Commission'),
                            'value' => $entry ? CommissionCategory::where('commission_id', $entry->id)->pluck('category_id')->toArray() : [],
                        ],
                        [
                            'type' => 'select',
                            'name' => 'role_id',
                            'options' => Helper::toJsOptions(Role::get(), ['id', 'name']),
                            'label' => __m('Role', 'Commission'),
                            'description' => __m('Assign this commission to users with the selected role.', 'Commission'),
                            'value' => $entry->role_id ?? '',
                            'validation' => 'required',
                        ],
                        [
                            'type' => 'textarea',
                            'name' => 'description',
                            'description' => __m('Provide additional details about this commission.', 'Commission'),
                            'label' => __m('Description', 'Commission'),
                            'value' => $entry->description ?? '',
                        ],
                    ],
                ],
                'product_values' => [
                    'label' => __m('Product Values', 'Commission'),
                    'fields' => [],
                    'component' => 'nsCommissionProductValues',
                    'show' => function ($fields) {
                        return ($fields['type'] ?? '') === Commission::TYPE_FIXED;
                    },
                ],
            ],
        ];
    }

    /**
     * Filter POST input fields
     */
    public function filterPostInputs($inputs): array
    {
        unset($inputs['categories']);
        return $inputs;
    }

    /**
     * Filter PUT input fields
     */
    public function filterPutInputs($inputs, Commission $entry): array
    {
        unset($inputs['categories']);
        return $inputs;
    }

    /**
     * Before saving a record
     */
    public function beforePost($inputs): array
    {
        if ($this->permissions['create'] !== false) {
            ns()->restrict($this->permissions['create']);
        } else {
            throw new NotAllowedException;
        }

        return $inputs;
    }

    /**
     * After saving a record
     */
    public function afterPost($inputs, Commission $entry): array
    {
        $this->saveCategories($inputs, $entry);
        return $inputs;
    }

    /**
     * Save category relationships
     */
    private function saveCategories($inputs, $entry): void
    {
        $categories = $inputs['categories'] ?? [];

        if (is_array($categories)) {
            foreach ($categories as $categoryId) {
                CommissionCategory::create([
                    'commission_id' => $entry->id,
                    'category_id' => $categoryId,
                ]);
            }
        }
    }

    /**
     * Before updating a record
     */
    public function beforePut($inputs, $entry): array
    {
        if ($this->permissions['update'] !== false) {
            ns()->restrict($this->permissions['update']);
        } else {
            throw new NotAllowedException;
        }

        return $inputs;
    }

    /**
     * After updating a record
     */
    public function afterPut($inputs, $entry): array
    {
        $entry->categories()->delete();
        $this->saveCategories($inputs, $entry);
        return $inputs;
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

        // Delete related categories
        $model->categories()->delete();
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return [
            'name' => [
                'label' => __m('Name', 'Commission'),
                '$direction' => '',
                '$sort' => true,
            ],
            'active' => [
                'label' => __m('Active', 'Commission'),
                '$direction' => '',
                '$sort' => false,
            ],
            'type' => [
                'label' => __m('Type', 'Commission'),
                '$direction' => '',
                '$sort' => false,
            ],
            'value' => [
                'label' => __m('Value', 'Commission'),
                '$direction' => '',
                '$sort' => false,
            ],
            'role_name' => [
                'label' => __m('Role', 'Commission'),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __m('Author', 'Commission'),
                '$direction' => '',
                '$sort' => false,
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

        // Format display values
        $entry->active = (bool) $entry->active ? __m('Yes', 'Commission') : __m('No', 'Commission');

        $typeLabels = [
            Commission::TYPE_ON_THE_HOUSE => __m('On The House', 'Commission'),
            Commission::TYPE_FIXED => __m('Fixed', 'Commission'),
            Commission::TYPE_PERCENTAGE => __m('Percentage', 'Commission'),
        ];
        $rawType = $entry->type;
        $entry->type = $typeLabels[$entry->type] ?? $entry->type;

        // Format value display
        $entry->value = $rawType === Commission::TYPE_PERCENTAGE
            ? $entry->value . '%'
            : ns()->currency->define($entry->value)->format();

        $entry->action(
            label: __m('Edit', 'Commission'),
            identifier: 'edit',
            url: ns()->url('/dashboard/' . $this->slug . '/edit/' . $entry->id),
            type: 'GOTO'
        );

        $entry->action(
            label: __m('Delete', 'Commission'),
            identifier: 'delete',
            url: ns()->url('/api/crud/commission.commissions/' . $entry->id),
            confirm: [
                'message' => __m('Would you like to delete this commission?', 'Commission'),
            ],
            type: 'DELETE'
        );

        return $entry;
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
                if ($entity instanceof Commission) {
                    $entity->categories()->delete();
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
            'list' => ns()->url('dashboard/commissions'),
            'create' => ns()->url('dashboard/commissions/create'),
            'edit' => ns()->url('dashboard/commissions/edit/'),
            'post' => ns()->url('api/crud/commission.commissions'),
            'put' => ns()->url('api/crud/commission.commissions/{id}'),
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
        return [];
    }
}
