<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base table name (without store prefix)
     */
    private string $baseTable = 'rencommissions_order_item_commissions';

    /**
     * Run the migrations.
     * Migration 4 of 4: Creates permanent order item commission records
     */
    public function up(): void
    {
        // Create base table
        $this->createTable($this->baseTable);

        // Create store-specific tables for multistore
        $this->createStoreTables();
    }

    /**
     * Create the order item commissions table
     */
    private function createTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->unsignedBigInteger('order_id')
                ->comment('Reference to nexopos_orders');
            $table->unsignedBigInteger('order_product_id')
                ->comment('Reference to nexopos_orders_products');
            $table->unsignedBigInteger('product_id')
                ->comment('Reference to nexopos_products');
            $table->string('commission_type', 50)
                ->comment('Type: percentage, fixed, on_the_house');
            $table->unsignedBigInteger('earner_id')
                ->comment('User who earns the commission');
            $table->unsignedBigInteger('assigned_by')
                ->comment('User who assigned the commission');
            $table->decimal('commission_rate', 10, 4)->nullable()
                ->comment('Rate used (percentage or fixed amount)');
            $table->decimal('unit_price', 10, 2)
                ->comment('Product unit price at order time');
            $table->decimal('quantity', 10, 2)
                ->comment('Product quantity');
            $table->decimal('commission_per_unit', 10, 2)
                ->comment('Calculated commission per unit');
            $table->decimal('total_commission', 10, 2)
                ->comment('Total commission (per_unit * quantity)');
            $table->json('calculation_details')->nullable()
                ->comment('JSON with calculation breakdown');
            $table->enum('status', ['pending', 'paid', 'cancelled', 'voided'])
                ->default('pending');
            $table->unsignedBigInteger('voided_by')->nullable()
                ->comment('User who voided the commission');
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->string('payment_reference', 100)->nullable()
                ->comment('External payment reference');
            $table->timestamps();

            // Indexes (keep names under 64 chars)
            $shortName = substr($tableName, 0, 25);
            $table->index('order_id', $shortName . '_order_idx');
            $table->index('order_product_id', $shortName . '_ordprod_idx');
            $table->index('product_id', $shortName . '_product_idx');
            $table->index('earner_id', $shortName . '_earner_idx');
            $table->index('assigned_by', $shortName . '_assignby_idx');
            $table->index('status', $shortName . '_status_idx');
            $table->index('created_at', $shortName . '_created_idx');
            $table->index(['earner_id', 'status'], $shortName . '_earner_stat_idx');
            $table->index(['order_id', 'status'], $shortName . '_order_stat_idx');
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
