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
        Schema::createIfMissing('nexopos_commission_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->foreign('commission_id')->references('id')->on('nexopos_commissions')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('nexopos_products_categories')->onDelete('cascade');
            
            $table->unique(['commission_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_commission_categories');
    }
};
