<?php

namespace Modules\NsCommissions\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Users;
use Illuminate\Http\Request;
use Modules\NsCommissions\Models\Commission;
use Modules\NsCommissions\Models\CommissionProductCategory;
use TorMorten\Eventy\Facades\Events as Hook;

class CommissionsCrud extends CrudService
{
    /**
     * Define the CRUD identifier
     */
    const IDENTIFIER = 'ns.commissions';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_commissions';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'commissions';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.commissions';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = Commission::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create'    =>  'ns.commissions-create',
        'read'      =>  'ns.commissions-read',
        'update'    =>  'ns.commissions-update',
        'delete'    =>  'ns.commissions-delete',
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        ['nexopos_users as user', 'user.id', '=', 'nexopos_commissions.author'],
        ['nexopos_roles as role', 'role.id', '=', 'nexopos_commissions.role_id'],
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations = [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    /**
     * Pick
     * Restrict columns you retreive from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick = [
        'user'  =>  ['username'],
        'role'  =>  ['name'],
    ];

    /**
     * Define where statement
     *
     * @var  array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var  array
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

    /**
     * Define Constructor
     *
     * @param
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return  array
     **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __m('Commissions List', 'NsCommissions'),
            'list_description'      =>  __m('Display all commissions.', 'NsCommissions'),
            'no_entry'              =>  __m('No commissions has been registered', 'NsCommissions'),
            'create_new'            =>  __m('Add a new commission', 'NsCommissions'),
            'create_title'          =>  __m('Create a new commission', 'NsCommissions'),
            'create_description'    =>  __m('Register a new commission and save it.', 'NsCommissions'),
            'edit_title'            =>  __m('Edit commission', 'NsCommissions'),
            'edit_description'      =>  __m('Modify  Commission.', 'NsCommissions'),
            'back_to_list'          =>  __m('Return to Commissions', 'NsCommissions'),
        ];
    }

    /**
     * Check whether a feature is enabled
     *
     * @return  bool
     **/
    public function isEnabled($feature): bool
    {
        return false; // by default
    }

    /**
     * Fields
     *
     * @param  object/null
     * @return  array of field
     */
    public function getForm($entry = null)
    {
        return [
            'main' =>  [
                'label'         =>  __m('Name', 'NsCommissions'),
                'name'          =>  'name',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __m('Provide a name to the resource.', 'NsCommissions'),
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __m('General', 'NsCommissions'),
                    'fields'    =>  [
                        [
                            'type'  =>  'switch',
                            'name'  =>  'active',
                            'options'   =>  Helper::kvToJsOptions([__m('No', 'NsCommission'), __m('Yes', 'NsCommissions')]),
                            'description'   =>  __m('Define wether the commissions is active or not.', 'NsCommissions'),
                            'label' =>  __m('Active', 'NsCommissions'),
                            'value' =>  $entry->active ?? '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'type',
                            'options'   =>  Helper::kvToJsOptions([
                                Commission::TYPE_ON_THE_HOUSE =>  __m('On The House', 'NsCommissions'),
                                Commission::TYPE_FIXED =>  __m('Fixed', 'NsCommissions'),
                                Commission::TYPE_PERCENTAGE =>  __m('Percentage', 'NsCommissions'),
                            ]),
                            'label' =>  __m('Type', 'NsCommissions'),
                            'description'   =>  __m('On The House: Fixed value regardless of discounts/taxes. Fixed: Individual commission per item. Percentage: Flat percentage for all items.', 'NsCommissions'),
                            'value' =>  $entry->type ?? Commission::TYPE_PERCENTAGE,
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'value',
                            'label' =>  __m('Default Value', 'NsCommissions'),
                            'description'   =>  __m('Default commission value. For Fixed type, per-product values can override this.', 'NsCommissions'),
                            'value' =>  $entry->value ?? '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'calculation_base',
                            'options'   =>  Helper::kvToJsOptions([
                                Commission::BASE_GROSS =>  __m('Gross Amount (before discount)', 'NsCommissions'),
                                Commission::BASE_NET =>  __m('Net Amount (after discount)', 'NsCommissions'),
                                Commission::BASE_FIXED =>  __m('Fixed (unit price)', 'NsCommissions'),
                            ]),
                            'label' =>  __m('Calculation Base', 'NsCommissions'),
                            'description'   =>  __m('For percentage type, determines what base amount to calculate from.', 'NsCommissions'),
                            'value' =>  $entry->calculation_base ?? Commission::BASE_GROSS,
                            'show'  =>  function ($fields) {
                                return $fields['type'] === Commission::TYPE_PERCENTAGE;
                            },
                        ], [
                            'type'  =>  'multiselect',
                            'options'   =>  Helper::toJsOptions(ProductCategory::get(), ['id', 'name']),
                            'name'  =>  'categories',
                            'label' =>  __m('Assigned Categories', 'NsCommissions'),
                            'description'   =>  __m('The commission will be effective for every products included on the selected categories.', 'NsCommissions'),
                            'value' =>  $entry ? CommissionProductCategory::where('commission_id', $entry->id)->get()->map(fn ($commissionProduct) => $commissionProduct->category_id)->toArray() : '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'role_id',
                            'options'   =>  Helper::toJsOptions(Role::get(), ['id', 'name']),
                            'label' =>  __m('Role', 'NsCommissions'),
                            'description'   =>  __m('Choose to assign the current commissions to a role.', 'NsCommissions'),
                            'value' =>  $entry->role_id ?? '',
                        ], [
                            'type'  =>  'textarea',
                            'name'  =>  'description',
                            'description'   =>  __m('Provide further details regarding the commissions.', 'NsCommissions'),
                            'label' =>  __m('Description', 'NsCommissions'),
                            'value' =>  $entry->description ?? '',
                        ],
                    ],
                ],
                'product_values' => [
                    'label'     =>  __m('Product Values', 'NsCommissions'),
                    'fields'    =>  [],
                    'component' =>  'nsCommissionProductValues',
                    'show'  =>  function ($fields) {
                        return $fields['type'] === Commission::TYPE_FIXED;
                    },
                ],
            ],
        ];
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs($inputs)
    {
        unset($inputs['categories']);

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs($inputs, Commission $entry)
    {
        unset($inputs['categories']);

        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request  $request
     * @return  void
     */
    public function beforePost($inputs)
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
     *
     * @param  Request  $request
     * @param  Commission  $entry
     * @return  void
     */
    public function afterPost($inputs, Commission $entry)
    {
        $this->saveCategories($inputs, $entry);

        return $inputs;
    }

    private function saveCategories($inputs, $entry)
    {
        $categories = $inputs['categories'];

        if (is_array($categories)) {
            foreach ($categories as $category) {
                $relationCategory = new CommissionProductCategory();
                $relationCategory->commission_id = $entry->id;
                $relationCategory->category_id = $category;
                $relationCategory->save();
            }
        }
    }

    /**
     * get
     *
     * @param  string
     * @return  mixed
     */
    public function get($param)
    {
        switch ($param) {
            case 'model': return $this->model; break;
        }
    }

    /**
     * Before updating a record
     *
     * @param  Request  $request
     * @param  object entry
     * @return  void
     */
    public function beforePut($request, $entry)
    {
        if ($this->permissions['update'] !== false) {
            ns()->restrict($this->permissions['update']);
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After updating a record
     *
     * @param  array  $inputs
     * @param  object entry
     * @return  void
     */
    public function afterPut($inputs, $entry)
    {
        $entry->categories()->delete();

        $this->saveCategories($inputs, $entry);

        return $inputs;
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete($namespace, $id, $model)
    {
        if ($namespace == 'ns.commissions') {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __m( 'You\re not allowed to do that.', 'NsCommissions' )
             *  ], 403 );
             **/
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
            } else {
                throw new NotAllowedException;
            }

            /**
             * Will delete all linked category
             */
            $model->categories()->delete();
        }
    }

    /**
     * Define Columns
     *
     * @return  array of columns configuration
     */
    public function getColumns(): array
    {
        return [
            'name'  =>  [
                'label'  =>  __m('Name', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'active'  =>  [
                'label'  =>  __m('Active', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'type'  =>  [
                'label'  =>  __m('Type', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'value'  =>  [
                'label'  =>  __m('Value', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'role_name'  =>  [
                'label'  =>  __m('Role', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
        ];
    }

    /**
     * Define actions
     */
    protected function setActions(CrudEntry $entry): CrudEntry
    {
        // Don't overwrite
        $entry->{ '$checked' } = false;
        $entry->{ '$toggled' } = false;
        $entry->{ '$id' } = $entry->id;

        $entry->active = (bool) $entry->active ? __m('Yes', 'NsCommissions') : __m('No', 'NsCommissions');
        
        // Format type display
        $typeLabels = [
            Commission::TYPE_ON_THE_HOUSE => __m('On The House', 'NsCommissions'),
            Commission::TYPE_FIXED => __m('Fixed', 'NsCommissions'),
            Commission::TYPE_PERCENTAGE => __m('Percentage', 'NsCommissions'),
        ];
        $entry->type = $typeLabels[$entry->type] ?? $entry->type;
        
        // Format value display
        $entry->value = $entry->type === Commission::TYPE_PERCENTAGE 
            ? $entry->value.'%' 
            : ns()->currency->define($entry->value)->format();

        // you can make changes here
        $entry->action(
            label: __m('Edit', 'NsCommissions'),
            identifier: 'edit',
            url: ns()->url('/dashboard/' . $this->slug . '/edit/' . $entry->id),
            type: 'GOTO'
        );

        $entry->action(
            label: __m('Delete', 'NsCommissions'),
            identifier: 'delete',
            url: ns()->url('/api/nexopos/v4/crud/ns.commissions/' . $entry->id),
            confirm: [
                'message' => __m('Would you like to delete this?', 'NsCommissions'),
            ],
            type: 'DELETE'
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction(Request $request)
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        if ($request->input('action') == 'delete_selected') {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
            } else {
                throw new NotAllowedException;
            }

            $status = [
                'success'   =>  0,
                'failed'    =>  0,
            ];

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

        return Hook::filter($this->namespace.'-catch-action', false, $request);
    }

    /**
     * get Links
     *
     * @return  array of links
     */
    public function getLinks(): array
    {
        return  [
            'list'      =>  ns()->url('dashboard/'.'commissions'),
            'create'    =>  ns()->url('dashboard/'.'commissions/create'),
            'edit'      =>  ns()->url('dashboard/'.'commissions/edit/'),
            'post'      =>  ns()->url('api/nexopos/v4/crud/'.'ns.commissions'),
            'put'       =>  ns()->url('api/nexopos/v4/crud/'.'ns.commissions/{id}'.''),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return  array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter($this->namespace.'-bulk', [
            [
                'label'         =>  __m('Delete Selected Groups', 'NsCommissions'),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace,
                ]),
            ],
        ]);
    }

    public function hook($query): void
    {
        $query->orderBy('created_at', 'desc');
    }

    /**
     * get exports
     *
     * @return  array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
