<?php

namespace Modules\NsMultiStore\Events;

use App\Classes\Output;
use App\Exceptions\NotAllowedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\NsMultiStore\Crud\StoreCrud;
use Modules\NsMultiStore\Crud\UsersCrud;
use Modules\NsMultiStore\Http\Middleware\DetectStoreMiddleware;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Settings\GeneralSettings;

/**
 * Register Events
 **/
class NsMultiStoreEvent
{
    public static $ignored_tables = [
        'nexopos_users',
        'nexopos_roles',
        'nexopos_permissions',
        'nexopos_role_permission',
        'nexopos_stores',
    ];

    public static $routePrefix = 'ns.multistore--';

    /**
     * Registers the crud component for
     * creating and managing stores
     *
     * @param string
     * @return string|\App\Services\Crud;
     */
    public static function registerCrud($crud)
    {
        switch ($crud) {
            case 'ns.multistore': return StoreCrud::class;
            case 'ns.multistore-users': return UsersCrud::class;
            default: return $crud;
        }
    }

    /**
     * Will overwite the way the url
     * helper is being used while on sub store
     *
     * @param string url
     * @return string;
     */
    public static function setUrl($url)
    {
        if (ns()->store->isMultiStore() && ! ns()->store->subDomainsEnabled()) {
            $isDashboardUrl = Str::of(url($url))->contains(url('/dashboard/'));
            $isApiUrl = Str::of(url($url))->contains(url('/api/'));

            /**
             * if while dividing the url using the segment "/dashboard"
             * the result equals 2, then that means the URL point to the "dashboard"
             * and not the "api".
             */
            if ($isDashboardUrl) {
                $url = collect(explode('dashboard', $url))
                    ->splice(1)
                    ->prepend('dashboard/store/'.ns()->store->getCurrentStore()->id)
                    ->prepend(url('/'))
                    ->map(fn ($slice) => (string) Str::of($slice)->trim('/'))
                    ->join('/');
            } elseif ($isApiUrl) {
                $url = collect(explode('api/', $url))
                    ->splice(1)
                    ->prepend('api/store/'.ns()->store->getCurrentStore()->id)
                    ->prepend(url('/'))
                    ->map(fn ($slice) => (string) Str::of($slice)->trim('/'))
                    ->join('/');
            }
        }

        return $url;
    }

    /**
     * This will include the current store id from
     * where the customer is being created
     * @param array $fields
     * return array
     */
    public static function customerFactory( $fields )
    {
        if ( ns()->store->isMultiStore() ) {
            $fields[ 'origin_store_id' ] = ns()->store->getCurrentStore()->id;
        }

        return $fields;
    }

    /**
     * Will overwite the way the url
     * helper is being used while on sub store
     *
     * @param string url
     * @return string;
     */
    public static function setAsset($url)
    {
        if (ns()->store->isMultiStore()) {
            /**
             * if while dividing the url using the segment "/dashboard"
             * the result equals 2, then that means the URL point to the "dashboard"
             * and not the "api".
             */
            $url = collect(explode('storage', $url))
                ->splice(1)
                ->prepend('storage/store_'.ns()->store->getCurrentStore()->id)
                ->prepend(url('/'))
                ->map(fn ($slice) => (string) Str::of($slice)->trim('/'))
                ->join('/');
        }

        return $url;
    }

    /**
     * register menus
     *
     * @param array menus
     * @return array
     */
    public static function dashboardMenus($menus)
    {
        /**
         * If we're browsing the multistore
         * let's display the default menus.
         */
        if (ns()->store->isMultiStore()) {
            unset($menus['modules']);
            unset($menus['users']);
            unset($menus['roles']);
            unset($menus['settings']['childrens']['workers']);

            /**
             * We'll add a users section only if
             * the subdomains are enabled.
             */
            if (ns()->store->subDomainsEnabled() && ns()->store->isMultiStore()) {
                $menus = array_insert_after($menus, 'customers', [
                    'ns.multistore-users'   =>  [
                        'label'             =>  __m('Users', 'NsMultiStore'),
                        'icon'              =>  'la-users',
                        'permissions'       =>  ['ns.multistore.read-users'],
                        'childrens'         =>  [
                            [
                                'label'         =>  __m('List', 'NsMultiStore'),
                                'href'          =>  ns()->route('ns.multistore-users.list'),
                                'permissions'   =>  ['ns.multistore.read-users'],
                            ], [
                                'label'         =>  __m('Create', 'NsMultiStore'),
                                'href'          =>  ns()->route('ns.multistore-users.create'),
                                'permissions'   =>  ['ns.multistore.create-users'],
                            ],
                        ],
                    ],
                ]);
            }

            return $menus;
        }

        $menus = array_insert_before($menus, 'modules', [
            'ns.multistore-dashboard'    =>  [
                'label'     =>  __m('Dashboard', 'NsMultiStore'),
                'icon'      =>  'la-home',
                'href'      =>  route('ns.multistore-dashboard'),
            ],
        ]);

        $menus = array_insert_before($menus, 'modules', [
            'ns.multistore-stores'    =>  [
                'label'     =>  __m('Stores', 'NsMultiStore'),
                'icon'      =>  'la-store',
                'permissions'   =>  ['ns.multistore.read.stores'],
                'childrens' =>  [
                    [
                        'label' =>  __m('List', 'NsMultiStore'),
                        'href'      =>  route('ns.multistore-stores'),
                        'permissions'   =>  ['ns.multistore.read.stores'],
                    ], [
                        'label' =>  __m('Create', 'NsMultiStore'),
                        'href'      =>  route('ns.multistore-stores.create'),
                        'permissions'   =>  ['ns.multistore.create.stores'],
                    ], [
                        'label' =>  __m('Settings', 'NsMultiStore'),
                        'href'      =>  route('ns.multistore-settings'),
                        'permissions'   =>  ['ns.multistore.access.root'],
                    ],
                ],
            ],
        ]);

        $menus = collect($menus)->filter(function ($menu, $index) {
            return in_array($index, ['modules', 'ns.multistore-settings', 'ns.multistore-dashboard', 'ns.multistore-stores', 'users', 'roles' ]);
        })->toArray();

        return $menus;
    }

    /**
     * This will make sure to overwrite the route
     * when the system is browsing a single store
     *
     * @param bool
     * @param string
     * @param array
     * @return bool|string
     */
    public static function builRoute($final, $route, $params)
    {
        if (ns()->store->isMultiStore()) {
            $baseRoutes = [
                'ns.dashboard.modules-list',
                'ns.dashboard.modules-upload',
                'ns.dashboard.modules-migrate',
            ];

            if (in_array($route, [
                ...$baseRoutes,
                ...(ns()->store->subDomainsEnabled() ? [] : [
                    'ns.dashboard.users.profile',
                    'ns.login',
                    'ns.register',
                    'ns.register.post',
                    'ns.logout',
                    'ns.database-update',
                ]),
            ])) {
                return route($route);
            }

            if (! ns()->store->subDomainsEnabled()) {
                return route(self::$routePrefix.$route, array_merge([
                    'store_id'  =>  ns()->store->getCurrentStore()->id,
                ], $params));
            } else {
                return route(self::$routePrefix.$route, array_merge([
                    'substore'  =>  ns()->store->getCurrentStore()->slug,
                ], $params));
            }
        } else {
            switch ($route) {
                case 'ns.dashboard.home':
                    return route('ns.multistore-dashboard');
            }
        }

        return $final;
    }

    /**
     * We'll inject the store selection menu.
     */
    public static function overWriteHeader($path)
    {
        return 'NsMultiStore::dashboard.header';
    }

    /**
     * Will provide a prefix on every named rounde
     * that are being registered as a sub store route
     *
     * @param string
     * @return string
     */
    public static function customizeRouteNames($name)
    {
        if (ns()->store->isMultiStore()) {
            return self::$routePrefix.$name;
        }

        return $name;
    }

    /**
     * We might want to check wether the user has some permission
     * to access the multistore dashboard. Preferably, we need to create
     * various dasboard for users roles,
     */
    public static function defaultRouteAfterAuthentication($route, $hadIntension)
    {
        if (! $hadIntension) {
            return route('ns.multistore-dashboard');
        }

        return $route;
    }

    /**
     * will force run the middleware on common routes
     * and check if an unauthorized access is detected
     */
    public static function disableDefaultComponents($response, $request, $next)
    {
        $detectStoreMiddleware = new DetectStoreMiddleware;
        $response = $detectStoreMiddleware->handle($request, $next);

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if (! ns()->store->isMultiStore()) {
            throw new NotAllowedException(__m('Unable to access to this page when the multistore is enabled.', 'NsMultiStore'));
        }

        return $response;
    }

    /**
     * will prefix model table while the
     * model is being used on  multistore
     *
     * @param  string  $table
     * @return string
     */
    public static function prefixModelTable($table)
    {
        return ns()->store->prefixTable( $table );
    }

    /**
     * We would like to prevent the store table to be ereased
     * while performing a reset.
     *
     * @param  string  $table name
     * @return mixed $table or boolean
     */
    public static function preventTableTruncatingOnMultiStore($table)
    {
        if (in_array($table, [
            'nexopos_stores',
            'nexopos_options',
        ])) {
            return false;
        }

        return $table;
    }

    public static function changeMediaDirectory($path)
    {
        if (ns()->store->isMultiStore()) {
            return 'store_'.ns()->store->getCurrentStore()->id.DIRECTORY_SEPARATOR.$path;
        }

        return $path;
    }

    /**
     * Return the settings object
     * once the settings page identifier is detected
     *
     * @param  string  $class
     * @param  string  $identifier
     * @return SettingsPage
     */
    public static function registerSettings($class, $identifier)
    {
        switch ($identifier) {
            case 'ns.multistore-settings' : return new GeneralSettings;
            default: return $class;
        }
    }

    /**
     * Will only returns roles that are
     * assigned to a store when we're browsing a store
     *
     * @param  Collection  $roles
     * @return Collection $roles
     */
    public static function filterRoles($roles)
    {
        if (ns()->store->isMultiStore() && ns()->store->subDomainsEnabled()) {
            $rolesIds = json_decode(Store::current()->roles_id);

            return $roles->filter(fn ($role) => in_array($role->id, $rolesIds));
        }

        return $roles;
    }

    public static function customerFilterInputs( $inputs )
    {
        if( isset( $inputs[ 'username' ] ) ) {
            $string = explode( '@', $inputs[ 'username' ] );
            $suffix = '_' . ns()->store->getCurrentStore()->id . '@';

            /**
             * We'll customize the username only if
             * that hasn't yet been customized.
             */
            if ( count( $string ) > 0 && strpos( $string[0], $suffix ) === false ) {
                $inputs[ 'username' ] = $string[0] . $suffix . $string[1];
            }
        }

        $inputs[ 'origin_store_id' ]    =   ns()->store->getCurrentStore()->id;

        return $inputs;
    }

    public static function addCachePrefix( $prefix )
    {
        if (ns()->store->isMultiStore()) {
            return 'store_'.ns()->store->getCurrentStore()->id.'_'.$prefix;
        }

        return $prefix;
    }
}
