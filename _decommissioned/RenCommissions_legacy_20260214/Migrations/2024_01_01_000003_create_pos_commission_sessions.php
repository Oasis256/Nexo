<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base table name (without store prefix)
     */
    private string $baseTable = 'rencommissions_pos_sessions';

    /**
     * Run the migrations.
     * Migration 3 of 4: Creates temporary POS commission session storage
     */
    public function up(): void
    {
        // Create base table
        $this->createTable($this->baseTable);

        // Create store-specific tables for multistore
        $this->createStoreTables();
    }

    /**
     * Create the POS commission sessions table
     */
    private function createTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->string('session_id', 100)
                ->comment('Laravel session ID');
            $table->integer('product_index')
                ->comment('Index of product in POS cart');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('staff_id')
                ->comment('Commission earner user ID');
            $table->string('commission_type', 50)
                ->comment('Type: percentage, fixed, on_the_house');
            $table->decimal('commission_value', 10, 2)->nullable()
                ->comment('User-entered value (rate or amount)');
            $table->decimal('commission_amount', 10, 2)
                ->comment('Calculated commission per unit');
            $table->decimal('unit_price', 10, 2)
                ->comment('Product unit price at assignment time');
            $table->decimal('quantity', 10, 2)->default(1)
                ->comment('Product quantity');
            $table->decimal('total_price', 10, 2)
                ->comment('unit_price * quantity');
            $table->decimal('total_commission', 10, 2)
                ->comment('commission_amount * quantity');
            $table->unsignedBigInteger('assigned_by')
                ->comment('User who assigned the commission');
            $table->timestamps();

            // Indexes for fast lookups (keep names under 64 chars)
            $shortName = substr($tableName, 0, 30);
            $table->index('session_id', $shortName . '_sess_idx');
            $table->index('product_id', $shortName . '_prod_idx');
            $table->index('staff_id', $shortName . '_staff_idx');
            $table->index(['session_id', 'product_index'], $shortName . '_sess_prod_idx');
            $table->index('created_at', $shortName . '_created_idx');
        });
    }

    /**
     * Create store-specific tables for multistore support
     */
    private function createStoreTables(): void
    {
        if (!class_exists('Modules\\NsMultiStore\\Models\\Store')) {
            return;
        }

        try {
            $stores = \Modules\NsMultiStore\Models\Store::all();
            
            foreach ($stores as $store) {
                $storeTable = 'store_' . $store->id . '_' . $this->baseTable;
                $this->createTable($storeTable);
            }
        } catch (\Exception $e) {
            // Multistore not available, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop base table
        Schema::dropIfExists($this->baseTable);

        // Drop store-specific tables
        if (!class_exists('Modules\\NsMultiStore\\Models\\Store')) {
            return;
        }

        try {
            $stores = \Modules\NsMultiStore\Models\Store::all();
            
            foreach ($stores as $store) {
                Schema::dropIfExists('store_' . $store->id . '_' . $this->baseTable);
            }
        } catch (\Exception $e) {
            // Skip
        }
    }
};
