<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rencommissions_order_item_commissions')) {
            Schema::create('rencommissions_order_item_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('order_product_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('earner_id');
                $table->unsignedBigInteger('type_id')->nullable();
                $table->enum('commission_method', ['percentage', 'fixed', 'on_the_house'])->default('fixed');
                $table->decimal('commission_value', 10, 2)->default(0);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('total_commission', 10, 2)->default(0);
                $table->enum('status', ['pending', 'paid', 'voided', 'cancelled'])->default('pending');
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->unsignedBigInteger('paid_by')->nullable();
                $table->dateTime('voided_at')->nullable();
                $table->unsignedBigInteger('voided_by')->nullable();
                $table->string('void_reason', 255)->nullable();
                $table->unsignedBigInteger('payout_id')->nullable();
                $table->timestamps();

                $table->index(['store_id', 'order_id', 'order_product_id'], 'rc_oic_store_order_product_idx');
                $table->index(['earner_id', 'status'], 'rc_oic_earner_status_idx');
                $table->index(['store_id', 'status', 'created_at'], 'rc_oic_store_status_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rencommissions_order_item_commissions');
    }
};
