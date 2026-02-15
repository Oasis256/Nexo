<?php

namespace Modules\NsCommissions\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates table for POS per-item commission user assignments
     */
    public function up(): void
    {
        Schema::createIfMissing('nexopos_order_product_commission_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('commission_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id', 'opca_order_fk')
                ->references('id')
                ->on('nexopos_orders')
                ->cascadeOnDelete();

            $table->foreign('order_product_id', 'opca_order_product_fk')
                ->references('id')
                ->on('nexopos_orders_products')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'opca_user_fk')
                ->references('id')
                ->on('nexopos_users')
                ->cascadeOnDelete();

            $table->foreign('commission_id', 'opca_commission_fk')
                ->references('id')
                ->on('nexopos_commissions')
                ->nullOnDelete();

            // Each order product can only have one commission assignment
            $table->unique('order_product_id', 'unique_order_product_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_order_product_commission_assignments');
    }
};
