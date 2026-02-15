<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates store-specific POS commission sessions tables for multistore support.
     * Table format: store_{store_id}_rencommissions_pos_sessions
     */
    public function up(): void
    {
        // Get the table name based on current store context
        $tableName = $this->getTableName();
        
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('session_id');
                $table->integer('product_index');
                $table->integer('product_id')->unsigned();
                $table->integer('staff_id')->unsigned();
                $table->enum('commission_type', ['percentage', 'fixed', 'on_the_house']);
                $table->decimal('commission_value', 10, 2)->nullable();
                $table->decimal('commission_amount', 10, 2);
                $table->decimal('unit_price', 10, 2);
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('total_price', 10, 2);
                $table->decimal('total_commission', 10, 2);
                $table->unsignedBigInteger('assigned_by');
                $table->timestamps();
                
                // Indexes for performance (using short custom names to avoid MySQL 64-char limit)
                $table->index(['session_id', 'product_index'], 'rc_pos_sess_idx');
                $table->index(['session_id', 'staff_id'], 'rc_pos_staff_idx');
                $table->index('product_id', 'rc_pos_prod_idx');
                $table->index('staff_id', 'rc_pos_stf_idx');
            });
        }
        
        // Migrate data from old non-prefixed table if it exists
        $oldTableName = 'rencommissions_pos_commission_sessions';
        if (Schema::hasTable($oldTableName) && Schema::hasTable($tableName)) {
            try {
                // Copy data from old table to new store-specific table
                $records = DB::table($oldTableName)->get();
                foreach ($records as $record) {
                    $payload = (array) $record;
                    if (!isset($payload['total_commission'])) {
                        $payload['total_commission'] = ($payload['commission_amount'] ?? 0) * ($payload['quantity'] ?? 1);
                    }
                    if (!isset($payload['assigned_by'])) {
                        $payload['assigned_by'] = 0;
                    }
                    if (($payload['commission_type'] ?? null) === 'flat') {
                        $payload['commission_type'] = 'fixed';
                    }
                    DB::table($tableName)->insert($payload);
                }
            } catch (\Exception $e) {
                // Ignore if data migration fails
                \Illuminate\Support\Facades\Log::warning('[RenCommissions] Failed to migrate POS session data: ' . $e->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = $this->getTableName();
        Schema::dropIfExists($tableName);
    }
    
    /**
     * Get the correct table name based on multistore context
     */
    private function getTableName(): string
    {
        // Check if we're in a multistore context
        if (class_exists('Modules\\NsMultiStore\\Services\\StoresService')) {
            try {
                $storesService = app('Modules\\NsMultiStore\\Services\\StoresService');
                
                // If we have a current store, use prefixed table name
                if ($storesService->isMultiStore() && $storesService->getCurrentStore()) {
                    $storeId = $storesService->getCurrentStore()->id;
                    return 'store_' . $storeId . '_rencommissions_pos_sessions';
                }
            } catch (\Exception $e) {
                // Multistore not available, use base table
            }
        }
        
        // Fallback to base table name (non-multistore or multistore disabled)
        return 'rencommissions_pos_sessions';
    }
};
