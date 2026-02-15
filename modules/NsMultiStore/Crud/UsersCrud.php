<?php

namespace Modules\NsMultiStore\Crud;

use App\Classes\CrudTable;
use App\Exceptions\NotAllowedException;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleRelation;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\Users;
use App\Services\UsersService;
use App\Services\WidgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Models\StoreRole;
use TorMorten\Eventy\Facades\Events as Hook;

class UsersCrud extends CrudService
{    
    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_users';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'users';

    /**
     * Define namespace
     *
     * @param  string
     */
    const IDENTIFIER = 'ns.multistore-users';

    protected $namespace = self::IDENTIFIER;

    const AUTOLOAD = true;

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = User::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create'    =>  true,
        'read'      =>  true,
        'update'    =>  true,
        'delete'    =>  true,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        'leftJoin'  =>  [
            ['nexopos_users as user', 'user.id', '=', 'nexopos_users.author'],
        ],
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
        'role'  =>  ['name'],
        'user'  =>  ['username'],
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

    private UsersService $userService;
    private WidgetService $widgetService;

    /**
     * Define Constructor
     *
     * @param
     */
    public function __construct()
    {
        parent::__construct();

        $this->userService = app()->make( UsersService::class );
        $this->widgetService = app()->make( WidgetService::class );
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
            'list_title'            =>  __m('Users List', 'NsMultiStore'),
            'list_description'      =>  __m('Display all users.', 'NsMultiStore'),
            'no_entry'              =>  __m('No users has been registered', 'NsMultiStore'),
            'create_new'            =>  __m('Add a new user', 'NsMultiStore'),
            'create_title'          =>  __m('Create a new user', 'NsMultiStore'),
            'create_description'    =>  __m('Register a new user and save it.', 'NsMultiStore'),
            'edit_title'            =>  __m('Edit user', 'NsMultiStore'),
            'edit_description'      =>  __m('Modify  User.', 'NsMultiStore'),
            'back_to_list'          =>  __m('Return to Users', 'NsMultiStore'),
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
                'label'         =>  __m('Username', 'NsMultiStore'),
                'name'          =>  'username',
                'value'         =>  $entry->username ?? '',
                'description'   =>  __m('Provide a unique username.', 'NsMultiStore'),
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __m('General', 'NsMultiStore'),
                    'fields'    =>  [
                        [
                            'type'  =>  'select',
                            'options'   =>  Helper::kvToJsOptions([
                                0   =>  __m('No', 'NsMultiStore'),
                                1   =>  __m('Yes', 'NsMultiStore'),
                            ]),
                            'name'  =>  'active',
                            'label' =>  __m('Active', 'NsMultiStore'),
                            'description'   =>  __m('Define if the user should be active.', 'NsMultiStore'),
                            'value' =>  $entry->active ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'email',
                            'validation'    =>  'email',
                            'label' =>  __m('Email', 'NsMultiStore'),
                            'description'   =>  __m('Provide a unique email for the user.', 'NsMultiStore'),
                            'value' =>  $entry->email ?? '',
                        ], [
                            'type'  =>  'password',
                            'name'  =>  'password',
                            'validation'    =>  collect([ $entry instanceof User ? 'sometimes' : 'min:6' ])->join( ',' ),
                            'label' =>  __m('Password', 'NsMultiStore'),
                            'description'   =>  __m('Set a secure and unique password for the user', 'NsMultiStore'),
                        ], [
                            'type'  =>  'password',
                            'name'  =>  'password_confirm',
                            'validation'    =>  'same:general.password',
                            'label' =>  __m('Password Confirmation', 'NsMultiStore'),
                            'description'   =>  __m('Should be similar as the password.', 'NsMultiStore'),
                        ], [
                            'type'          =>  'multiselect',
                            'options'       =>  StoreRole::where( 'store_id', Store::current()->id )->with( 'role' )
                                ->get()
                                ->map( fn( $role ) => [ 'value' => $role->role_id, 'label' => $role->role->name ] )->toArray(),
                            'name'          =>  'roles',
                            'label'         =>  __m('Roles', 'NsMultiStore'),
                            'description'   =>  __m('Choose the roles applicable to the users.', 'NsMultiStore'),
                            'value'         =>  $entry !== null ? ($entry->roles()->get()->map(fn ($role) => $role->id)->toArray() ?? '') : [],
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
        unset( $inputs[ 'roles' ] );
        unset($inputs['password_confirm']);

        if (! empty($inputs['password'])) {
            $inputs['password'] = Hash::make($inputs['password']);
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs($inputs, User $entry)
    {
        unset( $inputs[ 'roles' ] );
        unset($inputs['password_confirm']);

        if (! empty($inputs['password'])) {
            $inputs['password'] = Hash::make($inputs['password']);
        } else {
            /**
             * if the password is not set, then probably we
             * don't want to edit that.
             */
            unset($inputs['password']);
        }

        return $inputs;
    }

    private function validateInputs($inputs)
    {
        // ..
    }

    /**
     * Before saving a record
     *
     * @param  Request  $request
     * @return  void
     */
    public function beforePost($request)
    {
        $this->allowedTo( 'create' );

        return $request;
    }

    /**
     * After saving a record
     *
     * @param  Request  $request
     * @param  User  $entry
     * @return  void
     */
    public function afterPost($request, User $entry)
    {
        if ( isset( $request[ 'roles'] ) ) {

            /**
             * Additionnally while creating the user we need to
             * assign him the role that is assigned to the store so he has 
             * access to it by default.
             */
            $store  =   Store::current();
            $roles  =   json_decode( $store->roles_id );

            $this->userService
                ->setUserRole(
                    $entry,
                    [
                        ...$request[ 'roles' ],
                        ...$roles,
                    ]
                );

            $this->userService->createAttribute( $entry );

            /**
             * We'll add all the default
             * widget the user is allowed to see.
             */
            $this->widgetService->addDefaultWidgetsToAreas( $entry );
        }

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

    public function hook($query): void
    {
        $roles = json_decode(Store::current()->roles_id);

        $relation = UserRoleRelation::whereIn('role_id', $roles)
            ->get()
            ->map(fn ($relation) => $relation->user_id)
            ->toArray();

        $query->whereIn('nexopos_users.id', $relation);
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
        if ( isset( $request[ 'roles'] ) ) {

            /**
             * Additionnally while creating the user we need to
             * assign him the role that is assigned to the store so he has 
             * access to it by default.
             */
            $store  =   Store::current();
            $roles  =   json_decode( $store->roles_id );

            $this->userService
                ->setUserRole(
                    $entry,
                    [
                        ...$request[ 'roles' ],
                        ...$roles,
                    ]
                );

            $this->userService->createAttribute( $entry );
        }
        
        return $request;
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete($namespace, $id, $user)
    {
        if ($namespace == 'ns.multistore-users') {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __m( 'You\re not allowed to do that.', 'NsMultiStore' )
             *  ], 403 );
             **/
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);

                $this->restrictUserDeletion($user);
            } else {
                throw new NotAllowedException;
            }
        }
    }

    private function restrictUserDeletion($user)
    {
        $roles_id = json_decode(Store::current()->roles_id);

        $user->roles->each(function ($role) use ($roles_id) {
            if (! in_array($role->id, $roles_id)) {
                throw new NotAllowedException(__m('This user cannot be deleted from this store.', 'NsMultiStore'));
            }
        });

        if ($user->id === Auth::id()) {
            throw new NotAllowedException(__m('You cannot delete your own account.', 'NsMultiStore'));
        }
    }

    /**
     * Define Columns
     *
     * @return  array of columns configuration
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( __m( 'Username', 'NsMultistore' ), 'username' ),
            CrudTable::column( __m( 'Active', 'NsMultistore' ), 'active' ),
            CrudTable::column( __m( 'Email', 'NsMultistore' ), 'email' ),
            CrudTable::column( __m( 'Roles', 'NsMultistore' ), 'rolesNames' ),
            CrudTable::column( __m( 'Total Sales', 'NsMultistore' ), 'total_sales' ),
            CrudTable::column( __m( 'Author', 'NsMultistore' ), 'user_username' ),
            CrudTable::column( __m( 'Member Since', 'NsMultistore' ), 'created_at' ),
        );
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        // Don't overwrite
        $entry->{ '$checked' } = false;
        $entry->{ '$toggled' } = false;
        $entry->{ '$id' } = $entry->id;
        $entry->user_username = empty($entry->user_username) ? __m('N/A', 'NsMultiStore') : $entry->user_username;
        $entry->active = (bool) $entry->active ? __m('Yes', 'NsMultiStore') : __m('No', 'NsMultiStore');
        $entry->total_sales = ns()->currency->define($entry->total_sales)->format();

        $roles = User::find( $entry->id )->roles()->get();
        $entry->rolesNames = $roles->map( fn( $role ) => $role->name )->join( ', ' ) ?: __( 'Not Assigned' );

        // you can make changes here
        $entry->action( 
            identifier: 'edit',
            label:      __m('Edit', 'NsMultiStore'),
            type:      'GOTO',
            url:      ns()->url('/dashboard/'.$this->slug.'/edit/'.$entry->id),
        );

        $entry->action( 
            identifier: 'delete',
            label:  __m('Delete', 'NsMultiStore'),
            type:  'DELETE',
            url:  ns()->url('/api/crud/ns.multistore-users/'.$entry->id),
            confirm:  [
                'message'  =>  __m('Would you like to delete this ?', 'NsMultiStore'),
            ],
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
                'error'    =>  0,
            ];

            foreach ($request->input('entries') as $id) {
                $entity = $this->model::find($id);
                if ($entity instanceof User) {
                    $this->restrictUserDeletion($entity);
                    $entity->delete();
                    $status['success']++;
                } else {
                    $status['error']++;
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
            'list'      =>  ns()->url('dashboard/'.'users'),
            'create'    =>  ns()->url('dashboard/'.'users/create'),
            'edit'      =>  ns()->url('dashboard/'.'users/edit/'),
            'post'      =>  ns()->url('api/crud/'.'ns.multistore-users'),
            'put'       =>  ns()->url('api/crud/'.'ns.multistore-users/{id}'.''),
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
                'label'         =>  __m('Delete Selected Groups', 'NsMultiStore'),
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
