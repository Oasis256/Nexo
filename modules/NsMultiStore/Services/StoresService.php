<?php

namespace Modules\NsMultiStore\Services;

use App\Models\Migration;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleRelation;
use App\Services\EnvEditor;
use App\Services\ModulesService;
use App\Services\Options;
use App\Services\SetupService;
use App\Services\UsersService;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Database\Migrations\Migration as MigrationsMigration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Modules\NsMultiStore\Events\MultiStoreTablesCreatedEvent;
use Modules\NsMultiStore\Events\MultiStoreAfterDeletedEvent;
use Modules\NsMultiStore\Exceptions\NotAllowedStoreAccessException;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Models\StoreMigration;
use Modules\NsMultiStore\Models\StoreRole;

class StoresService
{
    private $isMultiStore = false;

    private $currentStore;

    public $ignored_tables = [
        'nexopos_users',
        'nexopos_roles',
        'nexopos_permissions',
        'nexopos_role_permission',
        'nexopos_stores',
    ];

    public function isMultiStore()
    {
        return $this->currentStore instanceof Store || $this->isMultiStore;
    }

    /**
     * This will only assign the working store
     * without defining how options are loaded. As this
     * method might be used when the store option is not yet set.
     * 
     * @param Store $store
     * @return void
     */
    public function setCurrentStore( Store $store )
    {
        $this->currentStore = $store;
    }

    public function setStore(Store $store)
    {
        $this->setCurrentStore( $store );
        
        /**
         * let's keep a reference to the root
         * settings accessible on each stores.
         */
        ns()->rootOption = ns()->option;

        /**
         * we need to reset the option used on ns() helper to ensure
         * it use the new initialiazed option object.
         */
        app()->singleton( Options::class, function() {
            return new Options;
        });

        ns()->option    =   app()->make( Options::class );

        /**
         * We would like to have unique cache key. When the store is set
         * we'll define a custom key for the cache.
         */
        $this->setCachePrefix( $store );
    }

    public function prefixTable( $table )
    {
        $table = trim($table);

        if ($this->isMultiStore() && ! in_array(trim($table), $this->ignored_tables)) {
            return 'store_'. $this->getCurrentStore()->id.'_'.$table;
        }

        return $table;
    }

    /**
     * @deprecated?
     */
    public function prefixTableName($table)
    {
        if ($this->isMultiStore()) {
            return 'store_'.$table;
        }

        return $table;
    }

    public function getCurrentStore()
    {
        return $this->currentStore;
    }

    public function current()
    {
        return $this->currentStore;
    }

    /**
     * get available migration for
     * a defined store
     * @param Store $store
     * @return Collection
     */
    public function getMigrations( Store $store ): Collection
    {
        /**
         * let's first purge the migration
         * that shouldn't be migrated
         */
        ns()->purgeMissingMigrations();

        /**
         * We'll now pull all the migration
         * file and execute them.
         */
        return collect(
            [
                ...$this->getModuleMigrations(),
                ...$this->getSystemMigrations(),
            ]
        )->diff(
            $this->getExecutedMigration($store)
                ->map(fn (StoreMigration $migration) => $migration->file)
        );
    }

    private function triggerMethod(Store $store, $className, $method)
    {
        $this->setCurrentStore( $store );

        /**
         * For laravel 9 and anonymous migration
         * we need to check the instance, to resolve it or not.
         */
        if ($className instanceof MigrationsMigration) {
            $classObject = $className;
        } else {
            $classObject = new $className;
        }

        /**
         * we'll now check if that class explicitely allow
         * execution on multistore or not.
         */
        if (method_exists($classObject, 'runOnMultiStore') && $classObject->runOnMultiStore() === false) {
            return;
        }

        $result = $classObject->$method();

        $this->unsetStore();

        return $result;
    }

    public function executeSingleMigration(Store $store, $response, $file)
    {
        if ($response['status'] === 'success') {
            $className = $response['data']['className'];
            $this->triggerMethod($store, $className, 'up');
        }
    }

    public function createStoreTables(Store $store)
    {
        ns()->store->setCurrentStore( $store );
        
        /**
         * This will execute first 
         * system migrations
         */
        collect([
            ...$this->getSystemMigrations(),
        ])->each( function($file) use ( $store ) {
            $result     =   $this->triggerFile($store, $file, 'up');
        });

        /**
         * This will then execute
         * module migrations
         */
        collect([
            ...$this->getModuleMigrations(),
        ])->each(fn ($file) => $this->triggerFile($store, $file, 'up'));

        /**
         * We'll create the default
         * payments type
         */
        ( new SetupService )->createDefaultPayment( User::find( $store->author ) );

        $options    =   new Options;
        $options->set( 'ns_pos_order_types',  ['takeaway', 'delivery']);

        $this->refreshStatefulDomains();

        /**
         * when a store table are being created
         * we'll deploy new event
         */
        MultiStoreTablesCreatedEvent::dispatch($store);
    }

    public function refreshStatefulDomains()
    {
        $stores = Store::status(Store::STATUS_OPENED)->get();
        $currentDomains = explode(',', env('SANCTUM_STATEFUL_DOMAINS', ''));

        // we should filter the $currentDomains to have unique values
        $currentDomains = array_unique($currentDomains);

        $domains = $stores->map(function ($store) {
            return $this->getStoreHost($store);
        })->filter(fn ($domain) => ! in_array($domain, $currentDomains))->toArray();

        $finalDomains = array_unique( array_merge($currentDomains, $domains ?: [], [$this->baseDomainName()]) );

        $env = app()->make( EnvEditor::class );
        $env->set('SANCTUM_STATEFUL_DOMAINS', implode(',', $finalDomains));

        return [
            'status'    =>  'success',
            'message'   =>  __m('The stateful domains has been refreshed.', 'NsMultiStore'),
        ];
    }

    /**
     * Will detect the migration class
     * and return wether it's from a module or not
     *
     * @param  string  $file
     * @return array $details
     */
    public function getMigrationFileDetails($file)
    {
        /**
         * @var ModulesService
         */
        $moduleService  =   app()->make( ModulesService::class );
        $info = pathinfo($file);
        $filename = $info['filename'];
        $moduleName = explode('/', $info['dirname'])[0];

        /**
         * We'll check if the migration file 
         * comes from a module or from the system.
         */
        if ( $moduleService->get( $moduleName ) ) {
            $className = 'Modules\\'.str_replace('/', '\\', $info['dirname']).'\\'.Str::studly($filename);
            $filePath   =   base_path( 'modules' . DIRECTORY_SEPARATOR . $file );

            /**
             * The module might be using a not anonymous class for the migration
             * in that case, we'll need to check if the class exists. 
             * @todo have we made any inclusion to check if that class exists ?
             */
            if ( class_exists( $className ) ) {
                return [
                    'className' =>  $className,
                    'module'    =>  $moduleName,
                    'path'      =>  base_path('modules/'.$file),
                ];
            } else if ( is_file( $filePath ) ) {
                return [
                    'className' =>  null,
                    'module'    =>  $moduleName,
                    'path'      =>  $filePath,
                ];
            } else {
                return [
                    'className' =>  null,
                    'module'    =>  null,
                    'path'      =>  null,
                ];
            }
        } else {
            /**
             * fetch all system migration
             * and return full path file and name
             */
            $allSystemMigration = collect(Storage::disk('ns')->allFiles('database/migrations'))->mapWithKeys(function ($file) {
                $info = pathinfo($file);

                return [$info['filename'] => $file];
            });

            /**
             * let's check if the file that is provided
             * actually belongs to the system.
             */
            if (in_array($file, $allSystemMigration->keys()->toArray())) {
                $filePath = base_path($allSystemMigration[$file]);

                if (is_file($filePath)) {
                    /**
                     * extract classname from file
                     */
                    $className = Str::studly(collect(explode('_', $file))
                        ->splice(4)
                        ->join('_'));

                    return [
                        'className' =>  $className,
                        'module'    =>  null,
                        'path'      =>  $filePath,
                    ];
                } else {
                    throw new Exception(sprintf(__m('The file "%s" doesn\'t exists', 'NsMultiStore'), $file));
                }
            } 

            /**
             * The file wasn't found. Probably deleted on the filesystem.
             * We don't need to run that and then we'll remove that from the executed migrations.
             */
            Migration::where( 'migration', $file )->delete();

            return [
                'className' =>  null,
                'module'    =>  null,
                'path'      =>  null,
            ];
        }        
    }

    /**
     * Trigger a specific migration file
     * by detecting wether it's from a module or a core migration
     *
     * @param  Store  $store
     * @param  string  $file
     * @param  string  $method
     * @return array $response
     */
    public function triggerFile(Store $store, $file, $method): array
    {
        /**
         * @param  string  $module
         * @param  string  $className
         * @param  string  $path
         */
        extract($this->getMigrationFileDetails($file));

        /**
         * in case nothing valid is returned, we might be having a declared
         * migration which file no longer exists. 
         * Therefore the file can't be triggered.
         */
        if( empty( $className ) && empty( $path ) && empty( $module ) ) {
            return [
                'status'    =>  'info',
                'message'   =>  __m( 'The migration file is missing.', 'NsMultiStore' ),
            ];
        }

        /**
         * let's include the file before 
         * triggering that
         */
        if( ! class_exists( $className ) && ! empty( $path ) ) {
            $className      =   include( $path );
        }

        $this->triggerMethod($store, $className, $method);

        $storeMigration = new StoreMigration;
        $storeMigration->module = $module;
        $storeMigration->store_id = $store->id;
        $storeMigration->file = $file;
        $storeMigration->save();

        return [
            'status'    =>  'success',
            'message'   =>  __m('The migration has been successfully executed.', 'NsMultiStore'),
            'data'      =>  [
                'migration'     =>  $storeMigration,
            ],
        ];
    }

    public function uninstallCoreMigration( $migration, $store ) 
    {
        $fullPath   =   base_path( 'database/migrations/create/' . $migration->file . '.php' );

        if ( is_file( $fullPath ) ) {
            /**
             * Include core file for the migration.
             */
            $object     =   include( $fullPath );

            $this->triggerMethod( $store, $object, 'down' );
        }
    }

    public function uninstallModuleMigration( $migration, $store ) 
    {
        $filePath   =   base_path( 'modules' . DIRECTORY_SEPARATOR . $migration->file );

        if ( is_file( $filePath ) ) {
            $className = 'Modules\\' . $migration->module . '\\Migrations\\' . Str::studly(pathinfo($migration->file)['filename']);

            if ( ! class_exists( $className ) ) {
                $className     =   include( $filePath );
            }

            $this->triggerMethod( $store, $className, 'down' );
        }
    }

    public function uninstallStore($store)
    {
        ns()->store->setCurrentStore( $store );

        /**
         * Remove all roles created
         * for the stores
         */
        $this->removeCreatedRoles( $store );

        /**
         * Get store migration files.
         */
        $migrations = StoreMigration::where('store_id', $store->id)->get();

        $migrations->each( function( $migration ) use ( $store ) {
            if ( $migration->module === null ) {
                $this->uninstallCoreMigration( $migration, $store );
            } else {
                $this->uninstallModuleMigration( $migration, $store );
            }

            $migration->delete();
        });        


        if (is_dir(storage_path('app/public/store_'.$store->id))) {
            Storage::disk('public')->deleteDirectory('store_'.$store->id);
        }
        
        $this->removeCreatedUsers( $store );
    }

    /**
     * This will remove all the created users
     * and their relationship with the roles.
     * @param Store $store
     * @return void
     */
    public function removeCreatedUsers( Store $store )
    {
        $users  =   User::where( 'origin_store_id', $store->id )->get();
        $usersIds   =   $users->map( fn( $user ) => $user->id );
        UserRoleRelation::whereIn( 'user_id', $usersIds )->delete();
        User::whereIn( 'id', $usersIds )->delete();
    }

    public function dismantleStore(Store $store)
    {
        $this->uninstallStore($store);
        
        MultiStoreAfterDeletedEvent::dispatch( $store );

        $store->delete();
    }

    public function unsetStore()
    {
        $this->currentStore = null;

        /**
         * we need to bind one more time the Options to point
         * to the root options
         */
        app()->singleton( Options::class, function() {
            return ns()->option = new Options;
        });

        /**
         * Now we've unset the store
         * we should reset the cache prefix.
         */
        $this->resetCachePrefix();
    }

    /**
     * This will set the cache prefix
     * to the store id.
     *
     * @param Store $store
     * @return void
     */
    public function setCachePrefix( Store $store )
    {
        $prefix     =   Str::slug( env( 'APP_NAME', 'laravel' ), '_' ) . '_cache_store_' . $store->id;
        
        if ( in_array( env( 'CACHE_DRIVER' ), [ 'redis', 'memcached' ] ) ) {
            Cache::getStore()->setPrefix( $prefix );
        } else {
            config([ 'cache.prefix' => $prefix ]);
        }

        app()->forgetInstance( 'cache' );
        app()->forgetInstance( Factory::class );

        /**
         * We need to rebind the cache manager
         * to ensure it uses the new prefix.
         */
        app()->bind( 'cache', function( $app ) {
            return new CacheManager( $app );
        });
    }

    /**
     * This will reset the cache prefix
     * to the original one.
     *
     * @return void
     */
    public function resetCachePrefix()
    {
        $originalPrefix     =   env( 'CACHE_PREFIX', Str::slug( env( 'APP_NAME', 'laravel' ), '_' ) . '_cache' );

        if ( in_array( env( 'CACHE_DRIVER' ), [ 'redis', 'memcached' ] ) ) {
            Cache::getStore()->setPrefix( $originalPrefix );
        } else {
            config([ 'cache.prefix' => $originalPrefix ]);
        }

        app()->forgetInstance( 'cache' );
        app()->forgetInstance( Factory::class );

        /**
         * We need to rebind the cache manager
         * to ensure it uses the new prefix.
         */
        app()->bind( 'cache', function( $app ) {
            return new CacheManager( $app );
        });
    }

    public function getStoreURL(Store $store)
    {
        return $this->baseProtocol().$this->getStoreHost($store);
    }

    public function getStoreHost(Store $store)
    {
        return $store->slug.'.'.$this->baseDomainName();
    }

    public function getOpened()
    {
        return Store::where('status', Store::STATUS_OPENED)->get();
    }

    /**
     * Will get opened store
     * to which the logged user has access to
     *
     * @return Collection<Store> a collection of stores.
     */
    public function getOpenedAccessibleStores()
    {
        $stores = Store::where('status', Store::STATUS_OPENED)->get();
        $roles = Auth::user()->roles;

        return $stores->filter(function ($store) use ($roles) {
            $decoded = json_decode($store->roles_id);

            return $roles->filter(fn ($role) => in_array($role->id, $decoded))->count() > 0;
        })->values();
    }

    /**
     * Will update all the store available for
     * a specific role
     *
     * @param  array|int  $roleId
     * @return array
     */
    public function countRoleStoresUsingID($roleId)
    {
        if (is_array($roleId)) {
            $result = [];

            foreach ($roleId as $_roleId) {
                $result[] = $this->countRoleStoresUsingID($_roleId);
            }

            return [
                'status'    =>  'info',
                'message'   =>  __m('The store count has been updated.', 'NsMultiStore'),
                'data'      =>  compact('result'),
            ];
        } else {
            $role = ! $roleId instanceof Role ? Role::find($roleId) : $roleId;

            if ( $role instanceof Role ) {
                $stores = Store::where('status', Store::STATUS_OPENED)->get();
    
                $role->total_stores = $stores->filter(function ($store) use ($role) {
                    $decoded = (array) json_decode($store->roles_id, true);
    
                    return in_array($role->id, $decoded);
                })->count();
    
                $role->save();
            }

            return [
                'status'    =>  'success',
                'message'   =>  __m('The role has been updated.', 'NsMultiStore'),
                'data'      =>  compact('role'),
            ];
        }
    }

    public function getModuleMigrations()
    {
        $module = app()->make(ModulesService::class);

        /**
         * the current module migration
         * shouldn't apply to the sub stores.
         */
        $migrationFiles = collect($module->getEnabled())->filter(function ($module) {
            return $module['namespace'] !== 'NsMultiStore';
        })->map(function ($module) {
            return $module['all-migrations'];
        })->flatten();

        return $migrationFiles;
    }

    /**
     * Return executed migration for a specifc
     * store
     *
     * @param  Store  $store
     * @return Collection
     */
    public function getExecutedMigration(Store $store)
    {
        return StoreMigration::forStore($store)
            ->get();
    }

    /**
     * Return a list of system migration
     *
     * @return Collection $migrations
     */
    public function getSystemMigrations()
    {
        /**
         * here are table that should be
         * created on sub stores.
         */
        return Migration::where( 'type', 'create' )->orderBy('migration', 'asc')
            ->get()
            ->filter(function ($file) {
                extract($this->getMigrationFileDetails($file->migration));

                if ( empty( $className ) && empty( $path ) ) {
                    return false;
                }

                /**
                 * @param  string  $className
                 * @param  string  $module
                 * @param  string  $path
                 */
                if ( ! class_exists( $className ) ) {
                    $object = include $path;
                } else {
                    $object = null;
                }

                if ( ! $object instanceof MigrationsMigration ) {
                    $object = new $className;
                }

                return ! property_exists($object, 'multistore') || $object->multistore;
            })
            ->map(fn ($migration) => $migration->migration);
    }

    /**
     * Defines the store routes
     *
     * @return void
     */
    public function defineStoreRoutes($callback)
    {
        $this->isMultiStore = true;
        $callback();
        $this->isMultiStore = false;
    }

    /**
     * Returns wether the sub domains are
     * currently enabled on the store
     *
     * @return bool
     */
    public function subDomainsEnabled()
    {
        return ns()->store->isMultiStore() ?
            ns()->rootOption->get('nsmultistore-subdomain', 'disabled') === 'enabled' :
            ns()->option->get('nsmultistore-subdomain', 'disabled') === 'enabled';
    }

    /**
     * Returns the actual base domain
     *
     * @param  string  $domain
     */
    public function baseDomainName()
    {
        $domain = pathinfo(env('APP_URL'));

        return $domain['filename'].(isset($domain['extension']) ? '.'.$domain['extension'] : '');
    }

    /**
     * Returns the current protocol used
     * on the store
     *
     * @return string $store
     */
    public function baseProtocol()
    {
        $domain = pathinfo(env('APP_URL'));

        return $domain['dirname'].'//';
    }

    /**
     * Will check if the currently logged user
     * has the right to access the store
     *
     * @return void
     */
    public function checkStoreAccessibility()
    {
        if (
            ns()->store->subDomainsEnabled() &&
            ns()->store->isMultiStore()
        ) {
            $storeRoles = (array) json_decode(Store::current()->roles_id, true);
            $roles = Auth::user()->roles;

            $hasAccess = $roles->filter(function ($role) use ($storeRoles) {
                return in_array($role->id, $storeRoles);
            });

            if ($hasAccess->isEmpty()) {
                throw new NotAllowedStoreAccessException(__m('You\'re not allowed to access to this store. If the problem persist, please contact the administrator.', 'NsMultiStore'));
            }
        }
    }

    /**
     * Will create the default access roles
     * for the store
     * @param Store $store
     */
    public function createDefaultAccessRoles( Store $store )
    {
        /**
         * @var UsersService
         */
        $userService        =   app()->make( UsersService::class );

        /** 
         * We start with the customer role
         */
        $userRole               =   Role::where( 'namespace', Role::USER )->first();
        $customerResult         =   $userService->cloneRole( $userRole, sprintf( __m( '%s — User Access', 'NsMultiStore' ), $store->name ), $store->id );
        $newUserRole            =   $customerResult[ 'data' ][ 'role' ];
        $newUserRole->locked    =   false;
        $newUserRole->save();
        
        $assignedRoles  =   json_decode( $store->roles_id ) ?: [];
        $roles          =   [ $newUserRole->id ];
        $finalRoles     =   array_unique( array_merge( $assignedRoles, $roles ) );

        $store->roles_id    =   json_encode( $finalRoles );
        $store->save();

        $usersWhoCanCreateStores    =   User::with( 'roles' )->whereHas("roles.permissions", function ($query) {
            $query->where("namespace", "ns.multistore.create.stores");
        })->get();

        /**
         * We'll give the access to access the created
         * store to the role who can create stores.
         */
        $usersWhoCanCreateStores->each( function( $user ) use( $userService, $newUserRole ) {
            $currentRoles   =   collect( $user->roles->map( fn( $role ) => $role->id )->toArray() );
            $currentRoles->push( $newUserRole->id );
            $userService->setUserRole( $user, $currentRoles );
        });
    }

    /**
     * Will remove the roles created for the store
     * @param Store $store
     */
    public function removeCreatedRoles( Store $store )
    {
        $roles  =   Role::whereIn( 'id', json_decode( $store->roles_id ) )->get();
        $roles->each( function( $role ) {
            if ( ! ( bool ) $role->locked ) {
                $role->permissions()->detach();
                $role->delete();
            }
        });        
    }

    public function setAllowedRoles( Store $store, array $allowedRoles )
    {
        StoreRole::where( 'store_id', $store->id )->delete();

        $allowedRoles = array_unique( $allowedRoles );

        $allowedRoles = array_map( function( $role ) use( $store ) {
            return [
                'role_id'   =>  $role,
                'store_id'  =>  $store->id,
            ];
        }, $allowedRoles );

        StoreRole::insert( $allowedRoles );
    }

    public function removeAllowedRoles( Store $store ): void
    {
        StoreRole::where( 'store_id', $store->id )->delete();
    }
}
