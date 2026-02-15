<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Base table name (without store prefix)
     */
    private string $baseTable = 'rencommissions_types';

    /**
     * Run the migrations.
     * Migration 2 of 4: Creates commission type definitions table
     */
    public function up(): void
    {
        // Create base table
        $this->createTable($this->baseTable);
        $this->seedDefaults($this->baseTable);

        // Create store-specific tables for multistore
        $this->createStoreTables();
    }

    /**
     * Create the commission types table
     */
    private function createTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($tableName) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->enum('calculation_method', ['percentage', 'fixed', 'on_the_house'])
                ->default('percentage');
            $table->decimal('default_value', 10, 2)->nullable()
                ->comment('Default rate/amount for this type');
            $table->decimal('min_value', 10, 2)->nullable()
                ->comment('Minimum allowed value');
            $table->decimal('max_value', 10, 2)->nullable()
                ->comment('Maximum allowed value');
            $table->boolean('is_active')->default(true);
            $table->boolean('apply_to_discounted')->default(true)
                ->comment('Apply commission to discounted items');
            $table->boolean('requires_approval')->default(false)
                ->comment('Requires manager approval');
            $table->integer('priority')->default(0)
                ->comment('Display order priority');
            $table->unsignedBigInteger('author')->nullable();
            $table->timestamps();

            // Indexes (keeping names under 64 chars)
            $shortName = substr($tableName, 0, 40);
            $table->index('is_active', $shortName . '_active_idx');
            $table->index('calculation_method', $shortName . '_method_idx');
            $table->index('priority', $shortName . '_priority_idx');
        });
    }

    /**
     * Seed default commission types
     */
    private function seedDefaults(string $tableName): void
    {
        $defaults = [
            [
                'name' => 'Percentage',
                'description' => 'Commission as percentage of product price',
                'calculation_method' => 'percentage',
                'default_value' => 5.00,
                'min_value' => 0.00,
                'max_value' => 100.00,
                'is_active' => true,
                'apply_to_discounted' => true,
                'requires_approval' => false,
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fixed Amount',
                'description' => 'Fixed commission from product commission_value',
                'calculation_method' => 'fixed',
                'default_value' => null,
                'min_value' => null,
                'max_value' => null,
                'is_active' => true,
                'apply_to_discounted' => true,
                'requires_approval' => false,
                'priority' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'On-The-House',
                'description' => 'Fixed amount from settings multiplied by quantity',
                'calculation_method' => 'on_the_house',
                'default_value' => 1.00,
                'min_value' => null,
                'max_value' => null,
                'is_active' => true,
                'apply_to_discounted' => true,
                'requires_approval' => false,
                'priority' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table($tableName)->insert($defaults);
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
                
                // Only seed if table was just created (no records)
                if (DB::table($storeTable)->count() === 0) {
                    $this->seedDefaults($storeTable);
                }
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
