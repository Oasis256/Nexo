<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds duration column to nexopos_products table for service time tracking
     * Multistore-aware: adds to both base and store-specific tables
     */
    public function up(): void
    {
        // Add columns to base products table
        $this->addColumnsToTable('nexopos_products');

        // Handle multistore: add columns to all store-prefixed product tables
        $this->addColumnsToStoreTables();
    }

    /**
     * Add extension columns to a specific table
     */
    private function addColumnsToTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            // Service duration in minutes
            if (!Schema::hasColumn($tableName, 'duration')) {
                $table->integer('duration')->nullable()->default(null)
                    ->comment('Service duration in minutes');
            }
        });
    }

    /**
     * Add columns to store-specific product tables
     */
    private function addColumnsToStoreTables(): void
    {
        if (!class_exists('Modules\\NsMultiStore\\Models\\Store')) {
            return;
        }

        try {
            $stores = \Modules\NsMultiStore\Models\Store::all();
            
            foreach ($stores as $store) {
                $tableName = 'store_' . $store->id . '_nexopos_products';
                $this->addColumnsToTable($tableName);
            }
        } catch (\Exception $e) {
            // Multistore not available or configured, skip silently
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove from base table
        $this->removeColumnsFromTable('nexopos_products');

        // Remove from store-specific tables
        if (!class_exists('Modules\\NsMultiStore\\Models\\Store')) {
            return;
        }

        try {
            $stores = \Modules\NsMultiStore\Models\Store::all();
            
            foreach ($stores as $store) {
                $tableName = 'store_' . $store->id . '_nexopos_products';
                $this->removeColumnsFromTable($tableName);
            }
        } catch (\Exception $e) {
            // Multistore not available, skip
        }
    }

    /**
     * Remove extension columns from a specific table
     */
    private function removeColumnsFromTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasColumn($tableName, 'duration')) {
                $table->dropColumn('duration');
            }
        });
    }
};
