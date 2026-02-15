<?php

namespace Modules\BookingVisitors\Providers;

use App\Classes\Hook;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Modules\BookingVisitors\Adapters\Local\LocalBookingSourceAdapter;
use Modules\BookingVisitors\Adapters\Local\LocalNotificationAdapter;
use Modules\BookingVisitors\Adapters\Local\LocalQrTokenProvider;
use Modules\BookingVisitors\Contracts\ChannelBookingSourceInterface;
use Modules\BookingVisitors\Contracts\NotificationChannelInterface;
use Modules\BookingVisitors\Contracts\QrTokenProviderInterface;
use Modules\BookingVisitors\Support\PermissionsRegistry;

class BookingVisitorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ChannelBookingSourceInterface::class, LocalBookingSourceAdapter::class);
        $this->app->bind(NotificationChannelInterface::class, LocalNotificationAdapter::class);
        $this->app->bind(QrTokenProviderInterface::class, LocalQrTokenProvider::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/Views', 'BookingVisitors');
        $this->registerPermissionsAndAssignAdmin();
        $this->registerMenus();
    }

    private function registerMenus(): void
    {
        Hook::addFilter('ns-dashboard-menus', function (array $menus) {
            $menus['bookingvisitors'] = [
                'label' => __m('Bookings & Visitors', 'BookingVisitors'),
                'icon' => 'la-calendar-check',
                'permissions' => ['nexopos.bookingvisitors.read'],
                'childrens' => [
                    'bookingvisitors-dashboard' => [
                        'label' => __m('Dashboard', 'BookingVisitors'),
                        'href' => ns()->route('bookingvisitors.dashboard'),
                        'permissions' => ['nexopos.bookingvisitors.read'],
                    ],
                    'bookingvisitors-bookings' => [
                        'label' => __m('Bookings', 'BookingVisitors'),
                        'href' => ns()->route('bookingvisitors.bookings'),
                        'permissions' => ['nexopos.bookingvisitors.read'],
                    ],
                    'bookingvisitors-checkins' => [
                        'label' => __m('Check-ins', 'BookingVisitors'),
                        'href' => ns()->route('bookingvisitors.checkins'),
                        'permissions' => ['nexopos.bookingvisitors.read'],
                    ],
                    'bookingvisitors-guests' => [
                        'label' => __m('Guest Access', 'BookingVisitors'),
                        'href' => ns()->route('bookingvisitors.guests'),
                        'permissions' => ['nexopos.bookingvisitors.guest.access'],
                    ],
                    'bookingvisitors-logs' => [
                        'label' => __m('Audit Logs', 'BookingVisitors'),
                        'href' => ns()->route('bookingvisitors.logs'),
                        'permissions' => ['nexopos.bookingvisitors.reports.read'],
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

        foreach (PermissionsRegistry::all() as $entry) {
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

        $admin->addPermissions(PermissionsRegistry::namespaces(), silent: true);

        if (Schema::hasTable('nexopos_users') && Schema::hasTable('nexopos_users_roles_relations')) {
            User::whereHas('roles', function ($query) use ($admin) {
                $query->where('nexopos_roles.id', $admin->id);
            })->pluck('id')->each(function ($id) {
                Cache::forget('ns-all-permissions-' . $id);
            });
        }
    }
}

