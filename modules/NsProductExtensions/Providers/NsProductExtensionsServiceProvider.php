<?php

namespace Modules\NsProductExtensions\Providers;

use App\Classes\Hook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Schema\Blueprint;

class NsProductExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Hook into product CRUD form to add duration field
        $this->hookProductCrudForm();

        // Hook into product CRUD table to add duration column
        $this->hookProductCrudTable();

        // Listen for multistore migration events to add column to new stores
        $this->listenForMultistoreMigrations();
    }

    /**
     * Add duration field to product CRUD form
     */
    private function hookProductCrudForm(): void
    {
        Hook::addFilter('ns-products-crud-form', function (array $form, $entry = null) {
            // Add duration field to the identification tab
            if (isset($form['variations'][0]['tabs']['identification']['fields'])) {
                $form['variations'][0]['tabs']['identification']['fields'][] = [
                    'type' => 'number',
                    'name' => 'duration',
                    'label' => __('Service Duration'),
                    'description' => __('Service duration in minutes (for service-based products).'),
                    'value' => $entry->duration ?? '',
                    'validation' => 'nullable|integer|min:0',
                ];
            }

            return $form;
        }, 20, 2);
    }

    /**
     * Add duration column to product CRUD table
     */
    private function hookProductCrudTable(): void
    {
        Hook::addFilter('App\Crud\ProductCrud@getColumns', function (array $columns) {
            // Add duration column after 'status' column
            $newColumns = [];
            foreach ($columns as $key => $config) {
                $newColumns[$key] = $config;
                if ($key === 'status') {
                    $newColumns['duration'] = [
                        'label' => __('Duration'),
                        '$direction' => '',
                        '$sort' => false,
                        'width' => '100px',
                    ];
                }
            }
            return $newColumns;
        }, 10);
    }

    /**
     * Listen for multistore migration events to add columns to new store tables
     */
    private function listenForMultistoreMigrations(): void
    {
        if (!class_exists('Modules\\NsMultiStore\\Events\\MultiStoreMigrationEvent')) {
            return;
        }

        Event::listen('Modules\\NsMultiStore\\Events\\MultiStoreMigrationEvent', function ($event) {
            $storeId = $event->store->id;
            $tableName = 'store_' . $storeId . '_nexopos_products';

            if (!Schema::hasTable($tableName)) {
                return;
            }

            if (!Schema::hasColumn($tableName, 'duration')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->integer('duration')->nullable()->default(null)
                        ->comment('Service duration in minutes');
                });
            }
        });
    }
}
