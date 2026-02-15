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
        Schema::createIfMissing('nexopos_commission_product_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('value', 18, 5)->default(0);
            $table->timestamps();

            $table->foreign('commission_id')->references('id')->on('nexopos_commissions')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('nexopos_products')->onDelete('cascade');
            
            $table->unique(['commission_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_commission_product_values');
    }
};
