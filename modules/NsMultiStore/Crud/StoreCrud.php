<?php

namespace Modules\NsMultiStore\Crud;

use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Exceptions\NotAllowedException;
use App\Models\Role;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\NsMultiStore\Jobs\DismantleStoreJob;
use Modules\NsMultiStore\Jobs\SetupStoreJob;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Models\StoreRole;
use Modules\NsMultiStore\Services\StoresService;
use TorMorten\Eventy\Facades\Events as Hook;

class StoreCrud extends CrudService
{
    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_stores';

    /**
     * default identifier
     *
     * @param  string
     */
    protected $identifier = 'multistore/stores';

    const AUTOLOAD = true;

    /**
     * Define namespace
     *
     * @param  string
     */
    const IDENTIFIER = 'ns.multistore';

    protected $namespace = self::IDENTIFIER;

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = Store::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create'    =>  'ns.multistore.create.stores',
        'read'      =>  'ns.multistore.read.stores',
        'update'    =>  'ns.multistore.update.stores',
        'delete'    =>  'ns.multistore.delete.stores',
    ];

    /**
     * Adding relation
     *
     * @param  array
     */
    public $relations = [
        ['nexopos_users as user', 'user.id', '=', 'nexopos_stores.author'],
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
    public $pick = [];

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

    public function hook( $query ): void
    {
        $query->orderBy( 'created_at', 'desc' );
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
            'list_title'            =>  __m('Stores List', 'NsMultiStore'),
            'list_description'      =>  __m('Display all stores.', 'NsMultiStore'),
            'no_entry'              =>  __m('No stores has been registered', 'NsMultiStore'),
            'create_new'            =>  __m('Add a new store', 'NsMultiStore'),
            'create_title'          =>  __m('Create a new store', 'NsMultiStore'),
            'create_description'    =>  __m('Register a new store and save it.', 'NsMultiStore'),
            'edit_title'            =>  __m('Edit store', 'NsMultiStore'),
            'edit_description'      =>  __m('Modify  Store.', 'NsMultiStore'),
            'back_to_list'          =>  __m('Return to Stores', 'NsMultiStore'),
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
        return CrudForm::form(
            main: FormInput::text(
                label: __m('Name', 'NsMultiStore'),
                name: 'name',
                value: $entry->name ?? '',
                description: __m('Provide a name to the resource.', 'NsMultiStore'),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __m('General', 'NsMultiStore'),
                    fields: CrudForm::fields(
                        FormInput::switch(
                            label: __m('Status', 'NsMultiStore'),
                            name: 'status',
                            value: $entry->status ?? Store::STATUS_OPENED,
                            description: __m('Determine wether the store is available or not.', 'NsMultiStore'),
                            options: Helper::kvToJsOptions([
                                Store::STATUS_CLOSED => __m('Closed', 'NsMultiStore'),
                                Store::STATUS_OPENED => __m('Opened', 'NsMultiStore'),
                            ]),
                        ),
                        FormInput::multiselect(
                            label: __m('Access Role', 'NsMultiStore'),
                            name: 'roles_id',
                            disabled: $entry === null,
                            value: $entry !== null ? (array) json_decode($entry->roles_id, true) : [],
                            description: __m('The role that is allowed to access the store.', 'NsMultiStore'),
                            options: Helper::toJsOptions(Role::where( 'locked', false )->get(), ['id', 'name']),
                        ),
                        FormInput::multiselect(
                            label: __m( 'Allowed Roles', 'NsMultiStore' ),
                            name: 'allowed_roles',
                            value: $entry instanceof Store ? StoreRole::where( 'store_id', $entry->id )->get()->pluck( 'role_id' ) : [],
                            options: Helper::toJsOptions( Role::whereNotIn( 'namespace', [
                                Role::ADMIN,
                            ])->get(), ['id', 'name'] ),
                            description: __m( 'The roles that can be created within the store. Beware of the role you make available.', 'NsMultiStore' ),
                        ),
                        FormInput::media(
                            label: __m('Preview', 'NsMultiStore'),
                            name: 'thumb',
                            value: $entry->thumb ?? '',
                            description: __m('A graphical preview of the store.', 'NsMultiStore'),
                        ),
                        FormInput::textarea(
                            label: __m('Description', 'NsMultiStore'),
                            name: 'description',
                            value: $entry->description ?? '',
                            description: __m('Further details about the store.', 'NsMultiStore'),
                        ),
                    )
                )
            )
        );
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs($inputs)
    {
        $inputs['roles_id'] = json_encode($inputs['roles_id']);
        $inputs['slug'] = Str::slug($inputs['name']);
        $inputs['status'] = Store::STATUS_BUILDING;
        $inputs['author'] = Auth::id();

        unset( $inputs[ 'allowed_roles' ] );

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs($inputs, Store $entry)
    {
        $inputs['roles_id'] = json_encode($inputs['roles_id']);
        $inputs['slug'] = Str::slug($inputs['name']);
        $inputs['author'] = Auth::id();

        unset( $inputs[ 'allowed_roles' ] );

        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  array  $fields
     * @return  void
     */
    public function beforePost($fields, $entry, $inputs)
    {
        if ($this->permissions['create'] !== false) {
            ns()->restrict($this->permissions['create']);
        } else {
            throw new NotAllowedException;
        }

        $store = Store::where('slug', $inputs['slug'])->first();

        if ($store instanceof Store) {
            throw new NotAllowedException(__m('A store using the same slug already exists.', 'NsMultiStore'));
        }

        return $fields;
    }

    /**
     * After saving a record
     *
     * @param  array  $unfiltredInputs
     * @param  Store  $entry
     * @return  void
     */
    public function afterPost($unfiltredInputs, Store $store)
    {
        /**
         * @var StoresService
         */
        $storesService = app()->make(StoresService::class);

        SetupStoreJob::dispatch($store)
            ->delay(now());

        $roles = json_decode($store->roles_id, true);

        $storesService->countRoleStoresUsingID($roles);
        $storesService->setAllowedRoles( $store, $unfiltredInputs[ 'allowed_roles' ] ?? [] );
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
     * @param  array  $unfiltredInputs
     * @param  object entry
     * @return  void
     */
    public function afterPut( $unfiltredInputs, $store )
    {
        /**
         * @var StoresService
         */
        $storesService = app()->make(StoresService::class);

        $roles = json_decode($store->roles_id, true);

        $storesService->countRoleStoresUsingID($roles);
        $storesService->setAllowedRoles( $store, $unfiltredInputs[ 'allowed_roles' ] ?? [] );
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete($namespace, $id, $model)
    {
        if ($namespace == 'ns.multistore') {
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
            } else {
                throw new NotAllowedException;
            }

            $model->status = Store::STATUS_DISMANTLING;
            $model->save();

            DismantleStoreJob::dispatch($model->toArray())
                ->delay(now());

            return [
                'status'    =>  'success',
                'message'   =>  sprintf(__m('"%s", is about to be dismantled', 'NsMultiStore'), $model->name),
            ];
        }
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( __m( 'Name', 'NsMultiStore' ), 'name' ),
            CrudTable::column( __m( 'Status', 'NsMultiStore' ), 'status' ),
            CrudTable::column( __m( 'Created At', 'NsMultiStore' ), 'created_at' ),
            CrudTable::column( __m( 'Author', 'NsMultiStore' ), 'user_username' ),
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

        $entry->action(
            identifier: 'edit',
            label: __m('Edit', 'NsMultiStore'),
            type: 'GOTO',
            url: url('/dashboard/'.'multistore/stores'.'/edit/'.$entry->id),
        );  

        if ( in_array( $entry->status, [ Store::STATUS_FAILED, Store::STATUS_OPENED, Store::STATUS_BUILDING ]) ) {
            $entry->action(
                identifier: 'rebuild',
                label: __m('Reinstall', 'NsMultiStore'),
                type: 'GET',
                confirm: [
                    'message'   =>      __m('Would you like to reinstall this store? All data will be wiped out. This operation can\'t be undone.', 'NsMultiStore'),
                ],
                url: url('/api/multistores/'. $entry->id.'/reinstall'),
            );
        }


        // you can make changes here
        $entry->action(
            identifier: 'delete',
            label: __m('Delete', 'NsMultiStore'),
            type: 'DELETE',
            url:  url('/api/crud/ns.multistore/'.$entry->id),
            confirm:  [
                'message'  =>  __m('Would you like to delete this ?', 'NsMultiStore'),
            ],
        );

        switch ($entry->status) {
            case Store::STATUS_OPENED :         $entry->status = __m('Opened', 'NsMultiStore'); break;
            case Store::STATUS_BUILDING :       $entry->status = __m('Building', 'NsMultiStore'); break;
            case Store::STATUS_FAILED :         $entry->status = __m('Error', 'NsMultiStore'); break;
            case Store::STATUS_CLOSED :         $entry->status = __m('Closed', 'NsMultiStore'); break;
            case Store::STATUS_DISMANTLING :    $entry->status = __m('Dismantling', 'NsMultiStore'); break;
            default: $entry->status = __m('Unknown Status', 'NsMultiStore'); break;
        }

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
         * and nexopos.store.administrator.
         */
        if ($request->input('action') == 'dismantle_selected') {

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

                if ($entity instanceof Store) {
                    $entity->status = Store::STATUS_DISMANTLING;
                    $entity->save();

                    DismantleStoreJob::dispatch($entity->toArray())
                        ->delay(now());

                    $status['success']++;
                } else {
                    $status['error']++;
                }
            }

            return $status;
        }

        return Hook::filter( self::IDENTIFIER .'-catch-action', false, $request);
    }

    /**
     * get Links
     *
     * @return  array of links
     */
    public function getLinks(): array
    {
        return  [
            'list'      =>  url('dashboard/'.'multistore/stores'),
            'create'    =>  url('dashboard/'.'multistore/stores/create'),
            'edit'      =>  url('dashboard/'.'multistore/stores/edit/'),
            'post'      =>  url('api/crud/'.'ns.multistore'),
            'put'       =>  url('api/crud/'.'ns.multistore/{id}'.''),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return  array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter(self::IDENTIFIER.'-bulk', [
            [
                'label'         =>  __m('Dismantle Selected Stores', 'NsMultiStore'),
                'identifier'    =>  'dismantle_selected',
                'url'           =>  route('ns.api.crud-bulk-actions', [
                    'namespace' =>  self::IDENTIFIER,
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
