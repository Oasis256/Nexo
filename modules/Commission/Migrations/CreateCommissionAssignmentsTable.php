<?php

namespace Modules\Commission\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table tracks POS user selections for commission assignment per order product.
     */
    public function up(): void
    {
        Schema::createIfMissing('nexopos_commission_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_product_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('commission_id')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('nexopos_orders')->onDelete('cascade');
            $table->foreign('order_product_id')->references('id')->on('nexopos_orders_products')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('nexopos_users')->onDelete('cascade');
            $table->foreign('commission_id')->references('id')->on('nexopos_commissions')->onDelete('set null');

            $table->unique(['order_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_commission_assignments');
    }
};
