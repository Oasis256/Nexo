<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migration 1 of 4: Extends nexopos_products table with commission_value column
     */
    public function up(): void
    {
        // Add commission_value to base products table
        if (Schema::hasTable('nexopos_products') && !Schema::hasColumn('nexopos_products', 'commission_value')) {
            Schema::table('nexopos_products', function (Blueprint $table) {
                $table->decimal('commission_value', 10, 2)->nullable()->default(null)
                    ->after('tax_value')
                    ->comment('Fixed commission value for this product');
            });
        }

        // Handle multistore: add column to all store-prefixed product tables
        $this->addColumnToStoreTables();
    }

    /**
     * Add commission_value column to store-specific product tables
     */
    private function addColumnToStoreTables(): void
    {
        if (!class_exists('Modules\\NsMultiStore\\Models\\Store')) {
            return;
        }

        try {
            $stores = \Modules\NsMultiStore\Models\Store::all();
            
            foreach ($stores as $store) {
                $tableName = 'store_' . $store->id . '_nexopos_products';
                
                if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'commission_value')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->decimal('commission_value', 10, 2)->nullable()->default(null)
                            ->after('tax_value')
                            ->comment('Fixed commission value for this product');
                    });
                }
            }
        } catch (\Exception $e) {
            // Multistore not available or configured, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from base table
        if (Schema::hasTable('nexopos_products') && Schema::hasColumn('nexopos_products', 'commission_value')) {
            Schema::table('nexopos_products', function (Blueprint $table) {
                $table->dropColumn('commission_value');
            });
        }

        // Remove from store-specific tables
        if (!class_exists('Modules\\NsMultiStore\\Models\\Store')) {
            return;
        }

        try {
            $stores = \Modules\NsMultiStore\Models\Store::all();
            
            foreach ($stores as $store) {
                $tableName = 'store_' . $store->id . '_nexopos_products';
                
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'commission_value')) {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->dropColumn('commission_value');
                    });
                }
            }
        } catch (\Exception $e) {
            // Skip
        }
    }
};
