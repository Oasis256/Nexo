<?php

namespace Modules\RenCommissions\Providers;

use App\Classes\Hook;
use App\Crud\ProductCrud;
use App\Events\OrderAfterCreatedEvent;
use App\Events\RenderFooterEvent;
use App\Events\OrderVoidedEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\RenCommissions\Support\PermissionsRegistry as RenCommissionsPermissions;
use Modules\RenCommissions\Listeners\OrderCreatedCommissionListener;
use Modules\RenCommissions\Listeners\StoreTablesCreatedEventListener;
use Modules\RenCommissions\Listeners\OrderVoidedCommissionListener;
use Modules\RenCommissions\Listeners\RenderFooterEventListener;

class RenCommissionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ...
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'RenCommissions');

        $this->registerPermissionsAndAssignAdmin();
        $this->registerMenus();
        $this->registerEventListeners();
        $this->registerOrderAttributeHooks();
        $this->registerProductCrudHooks();
    }

    private function registerEventListeners(): void
    {
        Event::listen(OrderAfterCreatedEvent::class, OrderCreatedCommissionListener::class);
        Event::listen(OrderVoidedEvent::class, OrderVoidedCommissionListener::class);
        Event::listen(RenderFooterEvent::class, RenderFooterEventListener::class);

        if (class_exists('Modules\\NsMultiStore\\Events\\MultiStoreTablesCreatedEvent')) {
            Event::listen('Modules\\NsMultiStore\\Events\\MultiStoreTablesCreatedEvent', StoreTablesCreatedEventListener::class);
        }
    }

    private function registerMenus(): void
    {
        Hook::addFilter('ns-dashboard-menus', function (array $menus) {
            $menus['rencommissions'] = [
                'label' => __m('Commissions', 'RenCommissions'),
                'icon' => 'la-coins',
                'permissions' => ['nexopos.rencommissions.read.dashboard'],
                'childrens' => [
                    'rencommissions-dashboard' => [
                        'label' => __m('Dashboard', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.dashboard'),
                        'permissions' => ['nexopos.rencommissions.read.dashboard'],
                    ],
                    'rencommissions-all' => [
                        'label' => __m('All Commissions', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.commissions'),
                        'permissions' => ['nexopos.rencommissions.read.commissions'],
                    ],
                    'rencommissions-staff' => [
                        'label' => __m('Staff Earnings', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.staff'),
                        'permissions' => ['nexopos.rencommissions.read.reports'],
                    ],
                    'rencommissions-pending' => [
                        'label' => __m('Pending Payouts', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.pending'),
                        'permissions' => ['nexopos.rencommissions.manage.payouts'],
                    ],
                    'rencommissions-payout-interface' => [
                        'label' => __m('Payout Interface', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.payout-interface'),
                        'permissions' => ['nexopos.rencommissions.manage.payouts'],
                    ],
                    'rencommissions-history' => [
                        'label' => __m('Payment History', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.history'),
                        'permissions' => ['nexopos.rencommissions.read.reports'],
                    ],
                    'rencommissions-types' => [
                        'label' => __m('Commission Types', 'RenCommissions'),
                        'href' => ns()->route('rencommissions.types'),
                        'permissions' => ['nexopos.rencommissions.read.types'],
                    ],
                    'rencommissions-settings' => [
                        'label' => __m('Commission Settings', 'RenCommissions'),
                        'href' => ns()->url('/dashboard/settings/rencommissions'),
                        'permissions' => ['manage.options'],
                    ],
                ],
            ];

            return $menus;
        });
    }

    private function registerPermissionsAndAssignAdmin(): void
    {
        if (! Schema::hasTable('nexopos_permissions') || ! Schema::hasTable('nexopos_roles')) {
            return;
        }

        $permissions = RenCommissionsPermissions::all();

        foreach ($permissions as $entry) {
            $permission = Permission::firstOrNew(['namespace' => $entry['namespace']]);
            $permission->name = $entry['name'];
            $permission->description = $entry['description'];
            $permission->namespace = $entry['namespace'];
            $permission->save();
        }

        $admin = Role::namespace(Role::ADMIN);
        if (! $admin) {
            return;
        }

        $admin->addPermissions(RenCommissionsPermissions::namespaces(), silent: true);

        if (Schema::hasTable('nexopos_users') && Schema::hasTable('nexopos_users_roles_relations')) {
            User::whereHas('roles', function ($query) use ($admin) {
                $query->where('nexopos_roles.id', $admin->id);
            })->pluck('id')->each(function ($id) {
                Cache::forget('ns-all-permissions-' . $id);
            });
        }
    }

    private function registerProductCrudHooks(): void
    {
        Hook::addFilter('ns-products-crud-form', function (array $form, $entry = null) {
            if (!isset($form['variations'][0]['tabs']['identification']['fields'])) {
                return $form;
            }

            $form['variations'][0]['tabs']['identification']['fields'][] = [
                'type' => 'number',
                'name' => 'commission_value',
                'label' => __m('Default Commission Value', 'RenCommissions'),
                'description' => __m('Default commission value for this product (used when assigning quickly).', 'RenCommissions'),
                'value' => $entry->commission_value ?? 0,
                'validation' => 'nullable|numeric|min:0',
            ];

            return $form;
        }, 20, 2);

        Hook::addFilter(ProductCrud::method('getColumns'), function (array $columns) {
            $result = [];

            foreach ($columns as $key => $config) {
                $result[$key] = $config;

                if ($key === 'status') {
                    $result['commission_value'] = [
                        'label' => __m('Default Commission', 'RenCommissions'),
                        '$direction' => '',
                        '$sort' => false,
                        'width' => '150px',
                    ];
                }
            }

            return $result;
        }, 20);

        Hook::addFilter(ProductCrud::method('filterPostInputs'), function (array $inputs) {
            $inputs['commission_value'] = (float) ($inputs['commission_value'] ?? 0);

            return $inputs;
        }, 20);

        Hook::addFilter(ProductCrud::method('filterPutInputs'), function (array $inputs) {
            $inputs['commission_value'] = (float) ($inputs['commission_value'] ?? 0);

            return $inputs;
        }, 20);
    }

    private function registerOrderAttributeHooks(): void
    {
        Hook::addFilter('ns-order-attributes', function (array $attributes) {
            if (! in_array('uuid', $attributes, true)) {
                $attributes[] = 'uuid';
            }

            return $attributes;
        });
    }
}
