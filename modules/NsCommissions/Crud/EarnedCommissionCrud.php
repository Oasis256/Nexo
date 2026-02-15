<?php

namespace Modules\NsCommissions\Crud;

use App\Classes\Currency;
use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Users;
use Illuminate\Http\Request;
use Modules\NsCommissions\Models\Commission;
use Modules\NsCommissions\Models\EarnedCommission;
use TorMorten\Eventy\Facades\Events as Hook;

class EarnedCommissionCrud extends CrudService
{
    const IDENTIFIER = 'ns.earned-commissions';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_orders_commissions';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'orders/earned-commissions';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.earned-commissions';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = EarnedCommission::class;

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
        ['nexopos_orders as order', 'order.id', '=', 'nexopos_orders_commissions.order_id'],
        ['nexopos_users as user', 'user.id', '=', 'nexopos_orders_commissions.user_id'],
        ['nexopos_commissions as commission', 'commission.id', '=', 'nexopos_orders_commissions.commission_id'],
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
        'order'         =>  ['code'],
        'user'          =>  ['username'],
        'commission'    =>  ['name'],
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
            'list_title'            =>  __m('Earned Commissions List', 'NsCommissions'),
            'list_description'      =>  __m('Display all earned commissions.', 'NsCommissions'),
            'no_entry'              =>  __m('No earned commissions has been registered', 'NsCommissions'),
            'create_new'            =>  __m('Add a new earned commission', 'NsCommissions'),
            'create_title'          =>  __m('Create a new earned commission', 'NsCommissions'),
            'create_description'    =>  __m('Register a new earned commission and save it.', 'NsCommissions'),
            'edit_title'            =>  __m('Edit earned commission', 'NsCommissions'),
            'edit_description'      =>  __m('Modify  Earned commission.', 'NsCommissions'),
            'back_to_list'          =>  __m('Return to Earned Commissions', 'NsCommissions'),
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
                            'type'  =>  'text',
                            'name'  =>  'value',
                            'label' =>  __m('Value', 'NsCommissions'),
                            'description'   =>  __m('Define the value of the commission.', 'NsCommissions'),
                            'value' =>  $entry->value ?? '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'user_id',
                            'label' =>  __m('User', 'NsCommissions'),
                            'description'   =>  __m('Define to which use the commission is assigned.', 'NsCommissions'),
                            'options'   =>  Helper::toJsOptions(User::get(), ['id', 'username']),
                            'value' =>  $entry->user_id ?? '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'order_id',
                            'options'   =>  Helper::toJsOptions(Order::get(), ['id', 'code']),
                            'description'   =>  __m('Each commissions should be attached to an existing order.', 'NsCommissions'),
                            'label' =>  __m('Order', 'NsCommissions'),
                            'value' =>  $entry->order_id ?? '',
                        ], [
                            'type'  =>  'select',
                            'name'  =>  'commission_id',
                            'options'   =>  Helper::toJsOptions(Commission::get(), ['id', 'name']),
                            'label' =>  __m('Commission', 'NsCommissions'),
                            'description'   =>  __m('This assigned commission will be used as a reference.', 'NsCommissions'),
                            'value' =>  $entry->commission_id ?? '',
                        ],
                    ],
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
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs($inputs, EarnedCommission $entry)
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request  $request
     * @return  void
     */
    public function beforePost($request)
    {
        if ($this->permissions['create'] !== false) {
            ns()->restrict($this->permissions['create']);
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After saving a record
     *
     * @param  Request  $request
     * @param  EarnedCommission  $entry
     * @return  void
     */
    public function afterPost($request, EarnedCommission $entry)
    {
        return $request;
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
     * @param  Request  $request
     * @param  object entry
     * @return  void
     */
    public function afterPut($request, $entry)
    {
        return $request;
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete($namespace, $id, $model)
    {
        if ($namespace == 'ns.earned-commissions') {
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
            'name'              =>  [
                'label'         =>  __m('Name', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'user_username'     =>  [
                'label'         =>  __m('User', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'order_code'        =>  [
                'label'         =>  __m('Order Code', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'value'             =>  [
                'label'         =>  __m('Value', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'commission_name'   =>  [
                'label'         =>  __m('Commission', 'NsCommissions'),
                '$direction'    =>  '',
                '$sort'         =>  false,
            ],
            'created_at'        =>  [
                'label'         =>  __m('Date', 'NsCommissions'),
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

        $entry->value = Currency::define($entry->value)->format();

        // you can make changes here
        $entry->addAction('edit', [
            'label'         =>      __m('Edit', 'NsCommissions'),
            'namespace'     =>      'edit',
            'type'          =>      'GOTO',
            'url'           =>      ns()->url('/dashboard/'.$this->slug.'/edit/'.$entry->id),
        ]);

        $entry->addAction('delete', [
            'label'     =>  __m('Delete', 'NsCommissions'),
            'namespace' =>  'delete',
            'type'      =>  'DELETE',
            'url'       =>  ns()->url('/api/nexopos/v4/crud/ns.earned-commissions/'.$entry->id),
            'confirm'   =>  [
                'message'  =>  __m('Would you like to delete this ?', 'NsCommissions'),
            ],
        ]);

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
                if ($entity instanceof EarnedCommission) {
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
            'list'      =>  ns()->url('dashboard/'.'orders/earned-commissions'),
            'create'    =>  ns()->url('dashboard/'.'orders/earned-commissions/create'),
            'edit'      =>  ns()->url('dashboard/'.'orders/earned-commissions/edit/'),
            'post'      =>  ns()->url('api/nexopos/v4/crud/'.'ns.earned-commissions'),
            'put'       =>  ns()->url('api/nexopos/v4/crud/'.'ns.earned-commissions/{id}'.''),
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
