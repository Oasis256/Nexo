<?php

namespace Modules\Skeleton\Providers;

use App\Classes\Hook;
use Illuminate\Support\ServiceProvider;

class SkeletonServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register module services
    }

    public function boot()
    {
        // Add menu items to dashboard
        Hook::addFilter('ns-dashboard-menus', function ($menus) {
            $menus['skeleton'] = [
                'label' => __('Skeleton'),
                'icon' => 'la-cube',
                'permissions' => ['skeleton.read.items'],
                'childrens' => [
                    'skeleton-dashboard' => [
                        'label' => __('Dashboard'),
                        'href' => ns()->url('/dashboard/skeleton'),
                        'permissions' => ['skeleton.read.items'],
                    ],
                    'skeleton-items' => [
                        'label' => __('Manage Items'),
                        'href' => ns()->url('/dashboard/skeleton/items'),
                        'permissions' => ['skeleton.read.items'],
                    ],
                    'skeleton-create' => [
                        'label' => __('Add New Item'),
                        'href' => ns()->url('/dashboard/skeleton/items/create'),
                        'permissions' => ['skeleton.create.items'],
                    ],
                    'skeleton-features' => [
                        'label' => __('Features'),
                        'href' => ns()->url('/dashboard/skeleton/features'),
                        'permissions' => ['skeleton.read.items'],
                    ],
                    'skeleton-settings' => [
                        'label' => __('Settings'),
                        'href' => ns()->url('/dashboard/skeleton/settings'),
                        'permissions' => ['skeleton.update.items'],
                    ],
                ],
            ];

            return $menus;
        });

        // Example: Add filter to modify data
        Hook::addFilter('skeleton.item.created', function ($item) {
            // Custom logic when item is created
            return $item;
        }, 10, 1);

        // Example: Add action hook
        Hook::addAction('skeleton.after.item.save', function ($item) {
            // Perform action after item is saved
        }, 10, 1);
    }
}
