<?php

namespace Modules\RenCommissions\Providers;

use App\Classes\Hook;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderVoidedEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\ModulesService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\RenCommissions\Listeners\OrderCreatedListener;
use Modules\RenCommissions\Listeners\OrderVoidedListener;
use Modules\RenCommissions\Services\PerItemCommissionService;
use Modules\RenCommissions\Services\PosCommissionCleanupService;

class RenCommissionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register singleton services
        $this->app->singleton(PerItemCommissionService::class, function ($app) {
            return new PerItemCommissionService();
        });

        $this->app->singleton(PosCommissionCleanupService::class, function ($app) {
            return new PosCommissionCleanupService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(ModulesService $modulesService): void
    {
        // Check if module is enabled
        if (ns()->option->get('rencommissions_enabled', 'yes') !== 'yes') {
            return;
        }

        $this->registerPermissions();
        $this->registerHooks();
        $this->registerEventListeners();
        $this->registerScheduledTasks();
        $this->hookProductCrudForm();
    }

    /**
     * Register module permissions
     */
    protected function registerPermissions(): void
    {
        // Only define permissions if constant is set (during migrations)
        if (!defined('NEXO_CREATE_PERMISSIONS')) {
            return;
        }

        $permissions = [
            // Commission types management
            ['namespace' => 'rencommissions.create.types', 'name' => 'Create Commission Types', 'description' => 'Can create commission types'],
            ['namespace' => 'rencommissions.read.types', 'name' => 'Read Commission Types', 'description' => 'Can view commission types'],
            ['namespace' => 'rencommissions.update.types', 'name' => 'Update Commission Types', 'description' => 'Can update commission types'],
            ['namespace' => 'rencommissions.delete.types', 'name' => 'Delete Commission Types', 'description' => 'Can delete commission types'],
            
            // Commission records management
            ['namespace' => 'rencommissions.read.commissions', 'name' => 'Read Commissions', 'description' => 'Can view commission records'],
            ['namespace' => 'rencommissions.update.commissions', 'name' => 'Update Commissions', 'description' => 'Can update commission records (void, mark paid)'],
            ['namespace' => 'rencommissions.manage.payouts', 'name' => 'Manage Payouts', 'description' => 'Can manage commission payouts'],
            ['namespace' => 'rencommissions.read.own', 'name' => 'Read Own Commissions', 'description' => 'Can view own commission records'],
            ['namespace' => 'rencommissions.earn.commissions', 'name' => 'Earn Commission', 'description' => 'Can earn commission on eligible sales'],
            
            // Reports
            ['namespace' => 'rencommissions.read.reports', 'name' => 'Read Commission Reports', 'description' => 'Can view commission reports'],
            
            // Admin
            ['namespace' => 'rencommissions.admin', 'name' => 'Commission Admin', 'description' => 'Full admin access to commission system'],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrNew(['namespace' => $perm['namespace']]);
            $permission->name = $perm['name'];
            $permission->namespace = $perm['namespace'];
            $permission->description = $perm['description'];
            $permission->save();
        }

        // Assign all to admin role
        $admin = Role::namespace(Role::ADMIN);
        if ($admin) {
            $admin->addPermissions(array_column($permissions, 'namespace'), silent: true);

            // Gate permissions are cached per user; drop stale entries after sync.
            User::whereHas('roles', function ($query) use ($admin) {
                $query->where('nexopos_roles.id', $admin->id);
            })->pluck('id')->each(function ($id) {
                Cache::forget('ns-all-permissions-' . $id);
            });
        }
    }

    /**
     * Register hooks for POS integration
     */
    protected function registerHooks(): void
    {
        // Note: POS cart button integration is handled via TypeScript (main.ts)
        // using POS.cartHeaderButtons or DOM manipulation patterns
        // The PHP side only injects the assets via RenderFooterEvent

        // Add commissions menus and settings access.
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            $menus['rencommissions'] = [
                'label' => __m('Commissions', 'RenCommissions'),
                'icon' => 'la-coins',
                'permissions' => ['rencommissions.read.commissions'],
                'childrens' => [
                    'rencommissions-dashboard' => [
                        'label' => __m('Dashboard', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.dashboard'),
                        'permissions' => ['rencommissions.read.commissions'],
                    ],
                    'rencommissions-all-commissions' => [
                        'label' => __m('All Commissions', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.commissions'),
                        'permissions' => ['rencommissions.read.commissions'],
                    ],
                    'rencommissions-staff-earnings' => [
                        'label' => __m('Staff Earnings', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.staff-earnings'),
                        'permissions' => ['rencommissions.read.commissions'],
                    ],
                    'rencommissions-pending-payouts' => [
                        'label' => __m('Pending Payouts', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.pending-payouts'),
                        'permissions' => ['rencommissions.manage.payouts'],
                    ],
                    'rencommissions-payment-history' => [
                        'label' => __m('Payment History', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.payment-history'),
                        'permissions' => ['rencommissions.read.commissions'],
                    ],
                    'rencommissions-types' => [
                        'label' => __m('Commission Types', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.types'),
                        'permissions' => ['rencommissions.read.types'],
                    ],
                ],
            ];

            if (isset($menus['profile'])) {
                $menus['profile']['childrens']['rencommissions-my-commissions'] = [
                    'label' => __m('My Commissions', 'RenCommissions'),
                    'href' => ns()->route('rencommissions.my-commissions'),
                    'permissions' => ['rencommissions.read.own'],
                ];
            }

            if (isset($menus['settings'])) {
                $menus['settings']['childrens']['rencommissions-settings'] = [
                    'label' => __m('Commissions', 'RenCommissions'),
                    'href' => ns()->route('ns.dashboard.settings', ['settings' => 'rencommissions']),
                    'permissions' => ['rencommissions.admin'],
                ];
            }

            return $menus;
        });

        // Inject POS assets via RenderFooterEvent
        Event::listen(\App\Events\RenderFooterEvent::class, function ($event) {
            // Only inject on POS page
            if ($event->routeName === 'ns.dashboard.pos') {
                $event->output->addView('RenCommissions::pos.inject-assets');
            }
        });
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        // When order is created, convert session commissions
        Event::listen(OrderAfterCreatedEvent::class, OrderCreatedListener::class);

        // When order is voided, void related commissions
        Event::listen(OrderVoidedEvent::class, OrderVoidedListener::class);
    }

    /**
     * Register scheduled tasks
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            if (ns()->option->get('rencommissions_auto_cleanup', 'yes') === 'yes') {
                $frequency = ns()->option->get('rencommissions_cleanup_frequency', 'daily');
                
                $task = $schedule->call(function () {
                    app()->make(PosCommissionCleanupService::class)->runCleanup();
                });

                switch ($frequency) {
                    case 'hourly':
                        $task->hourly();
                        break;
                    case 'weekly':
                        $task->weekly();
                        break;
                    default:
                        $task->daily();
                }
            }
        });
    }

    /**
     * Add commission_value field to product CRUD form
     */
    private function hookProductCrudForm(): void
    {
        Hook::addFilter('ns-products-crud-form', function (array $form, $entry = null) {
            // Add commission_value field to the identification tab
            if (isset($form['variations'][0]['tabs']['identification']['fields'])) {
                $form['variations'][0]['tabs']['identification']['fields'][] = [
                    'type' => 'number',
                    'name' => 'commission_value',
                    'label' => __m('Commission Value', 'RenCommissions'),
                    'description' => __m('Default commission value for this product (used with "fixed" commission type).', 'RenCommissions'),
                    'value' => $entry->commission_value ?? '',
                    'validation' => 'nullable|numeric|min:0',
                ];
            }

            return $form;
        }, 20, 2);

        // Add commission_value column to product CRUD table
        Hook::addFilter('App\Crud\ProductCrud@getColumns', function (array $columns) {
            // Add commission_value column after 'duration' or 'status' column
            $newColumns = [];
            $inserted = false;
            
            foreach ($columns as $key => $config) {
                $newColumns[$key] = $config;
                
                // Insert after duration if it exists, otherwise after status
                if (($key === 'duration' || (!$inserted && $key === 'status'))) {
                    $newColumns['commission_value'] = [
                        'label' => __m('Commission', 'RenCommissions'),
                        '$direction' => '',
                        '$sort' => false,
                        'width' => '100px',
                    ];
                    $inserted = true;
                }
            }
            
            // If not inserted yet, add at the end
            if (!$inserted) {
                $newColumns['commission_value'] = [
                    'label' => __m('Commission', 'RenCommissions'),
                    '$direction' => '',
                    '$sort' => false,
                    'width' => '100px',
                ];
            }
            
            return $newColumns;
        }, 15);
    }
}

