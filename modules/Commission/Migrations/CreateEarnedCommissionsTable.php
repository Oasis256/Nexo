<?php

namespace Modules\Commission\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::createIfMissing('nexopos_earned_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('value', 18, 5)->default(0);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('quantity', 18, 5)->default(1);
            $table->unsignedBigInteger('commission_id')->nullable();
            $table->enum('commission_type', ['on_the_house', 'fixed', 'percentage'])->default('percentage');
            $table->decimal('base_amount', 18, 5)->default(0);
            $table->unsignedBigInteger('author')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('nexopos_users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('nexopos_orders')->onDelete('cascade');
            $table->foreign('order_product_id')->references('id')->on('nexopos_orders_products')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('nexopos_products')->onDelete('set null');
            $table->foreign('commission_id')->references('id')->on('nexopos_commissions')->onDelete('set null');
            $table->foreign('author')->references('id')->on('nexopos_users')->onDelete('set null');

            $table->index(['user_id', 'created_at']);
            $table->index(['order_id']);
            $table->index(['commission_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_earned_commissions');
    }
};
