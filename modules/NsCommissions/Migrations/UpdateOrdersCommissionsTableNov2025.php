<?php

namespace Modules\NsCommissions\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates earned commissions table to link to specific order products
     */
    public function up(): void
    {
        Schema::table('nexopos_orders_commissions', function (Blueprint $table) {
            // Add order_product_id for per-item tracking
            if (!Schema::hasColumn('nexopos_orders_commissions', 'order_product_id')) {
                $table->unsignedBigInteger('order_product_id')->nullable()->after('order_id');
                $table->foreign('order_product_id', 'oc_order_product_fk')
                    ->references('id')
                    ->on('nexopos_orders_products')
                    ->nullOnDelete();
            }

            // Add product_id for direct product reference
            if (!Schema::hasColumn('nexopos_orders_commissions', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('order_product_id');
                $table->foreign('product_id', 'oc_product_fk')
                    ->references('id')
                    ->on('nexopos_products')
                    ->nullOnDelete();
            }

            // Add quantity for calculation reference
            if (!Schema::hasColumn('nexopos_orders_commissions', 'quantity')) {
                $table->decimal('quantity', 18, 5)->default(1)->after('product_id');
            }

            // Add commission_type for denormalized tracking
            if (!Schema::hasColumn('nexopos_orders_commissions', 'commission_type')) {
                $table->string('commission_type', 50)->nullable()->after('commission_id');
            }

            // Add base_amount for calculation reference
            if (!Schema::hasColumn('nexopos_orders_commissions', 'base_amount')) {
                $table->decimal('base_amount', 18, 5)->default(0)->after('commission_type');
            }

            // Add indexes for performance
            $table->index('order_product_id', 'idx_order_product');
            $table->index(['user_id', 'created_at'], 'idx_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nexopos_orders_commissions', function (Blueprint $table) {
            // Drop foreign keys first using explicit names
            $table->dropForeign('oc_order_product_fk');
            $table->dropForeign('oc_product_fk');

            // Drop indexes
            $table->dropIndex('idx_order_product');
            $table->dropIndex('idx_user_date');

            // Drop columns
            $table->dropColumn([
                'order_product_id',
                'product_id',
                'quantity',
                'commission_type',
                'base_amount'
            ]);
        });
    }
};
