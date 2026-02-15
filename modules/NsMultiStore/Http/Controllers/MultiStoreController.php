<?php

/**
 * NexoPOS MultiStore Controller
 *
 * @since  1.0
 **/

namespace Modules\NsMultiStore\Http\Controllers;

use App\Exceptions\NotAllowedException;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Services\DateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Modules\NsMultiStore\Crud\StoreCrud;
use Modules\NsMultiStore\Crud\UsersCrud;
use Modules\NsMultiStore\Jobs\SetupStoreJob;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Models\StoreMigration;
use Modules\NsMultiStore\Services\StoresService;
use Modules\NsMultiStore\Settings\GeneralSettings;

class MultiStoreController extends DashboardController
{
    /**
     * @var StoresService
     */
    protected $storesService;

    public function __construct(
        StoresService $stores,
        DateService $dateService
    ) {
        parent::__construct( $dateService );

        $this->storesService = $stores;
        $this->dateService = $dateService;
    }

    /**
     * Index Controller Page
     *
     * @return  view
     *
     * @since  1.0
     **/
    public function home()
    {
        return View::make('NsMultiStore::dashboard.home', [
            'title'     =>      __m('MultiStore Dashboard', 'NsMultiStore'),
        ]);
    }

    public function stores()
    {
        return StoreCrud::table();
    }

    public function create()
    {
        return StoreCrud::form();
    }

    public function edit(Store $store)
    {
        return StoreCrud::form($store);
    }

    public function getStores()
    {
        return $this->storesService->getOpenedAccessibleStores();
    }

    public function getStoreDetails()
    {
        return Store::status(Store::STATUS_OPENED)
            ->get()
            ->map(function ($store) {
                $today = $this->dateService->copy()->now();
                $yesterday = $this->dateService->copy()->now()->subDay();

                $store->yesterday_report = DB::table('store_'.$store->id.'_nexopos_dashboard_days')
                    ->where('range_starts', '>=', $yesterday->startOfDay()->toDateTimeString())
                    ->where('range_ends', '<=', $yesterday->endOfDay()->toDateTimeString())
                    ->first();

                $store->today_report = DB::table('store_'.$store->id.'_nexopos_dashboard_days')
                    ->where('range_starts', '>=', $today->startOfDay()->toDateTimeString())
                    ->where('range_ends', '<=', $today->endOfDay()->toDateTimeString())
                    ->first();

                return $store;
            });
    }

    public function selectStore()
    {
        return View::make('NsMultiStore::dashboard.select-store', [
            'title'     =>      __m('Select Store', 'NsMultiStore'),
            'stores'    =>      $this->storesService->getOpenedAccessibleStores(),
        ]);
    }

    public function runMigration($store_id, Request $request)
    {
        $store = Store::findOrFail($store_id);
        
        Store::switchTo($store);

        return $this->storesService->triggerFile(
            $store,
            $request->input('file'),
            'up'
        );
    }

    public function migrateStore(Store $store)
    {
        $migrations     =   $this->storesService->getMigrations( $store );

        return View::make('NsMultiStore::dashboard.migrate', [
            'title'         =>  sprintf(__m('Database Migration : %s', 'NsMultiStore'), $store->name),
            'store'         =>  $store,
            'migrations'    =>  $migrations,
            'callback'      =>  request()->session()->get('multistore-cb') ?: ns()->route('ns.dashboard.home', [
                'store'     =>  $store->id,
            ]),
        ]);
    }

    public function subDomainHome(Request $request)
    {
        $store = ns()->store->current();
        $title = sprintf(__m('%s &mdash; Store', 'NsMultiStore'), $store->name);

        return View::make('NsMultiStore::frontend.home', compact('store', 'title'));
    }

    /**
     * @deprecated
     */
    public function rebuildStore(Store $store)
    {
        if ($store->status === Store::STATUS_FAILED) {
            $store->status = Store::STATUS_BUILDING;
            $store->save();

            SetupStoreJob::dispatch($store);

            return [
                'status'    =>  'success',
                'message'   =>  __m( 'The store is about to be rebuilt', 'NsMultiStore' )
            ];
        }

        throw new NotAllowedException(__m('Not allowed to rebuild a store with a wrong failed status.', 'NsMultiStore'));
    }

    public function settings()
    {
        return GeneralSettings::renderForm();
    }

    public function listUsers()
    {
        return UsersCrud::table();
    }

    public function createUser()
    {
        return UsersCrud::form();
    }

    public function editUser(User $user)
    {
        $store_roles = json_decode(Store::current()->roles_id);

        $hasRoles = $user->roles->map( fn( $role ) => $role->id )
            ->filter( fn( $roleId ) => in_array( $roleId, $store_roles ) )
            ->isNotEmpty();

        if ( ! $hasRoles ) {
            throw new NotAllowedException(__m('This user cannot be edited here.', 'NsMultiStore'));
        }

        if ( $user->id === Auth::id() ) {
            return redirect(ns()->route('ns.dashboard.users.profile'));
        }

        return UsersCrud::form($user);
    }

    public function reInstall(Store $store)
    {
        /**
         * @var StoresService
         */
        $storesService = app()->make(StoresService::class);

        /**
         * Let's start by uninstalling all tables
         */
        $storesService->uninstallStore($store);

        SetupStoreJob::dispatch( $store );

        /**
         * We'll dynamically change the status
         */
        $store->status = Store::STATUS_BUILDING;
        $store->save();

        return [
            'status'    =>  'success',
            'message'   =>  __m('The store is about to be reinstalled.', 'NsMultiStore'),
        ];
    }
}
