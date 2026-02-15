<?php

namespace Modules\NsCommissions\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates table for per-product commission values (Fixed type)
     */
    public function up(): void
    {
        Schema::createIfMissing('nexopos_commission_product_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commission_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('value', 18, 5)->default(0);
            $table->timestamps();

            $table->foreign('commission_id', 'cpv_commission_fk')
                ->references('id')
                ->on('nexopos_commissions')
                ->cascadeOnDelete();

            $table->foreign('product_id', 'cpv_product_fk')
                ->references('id')
                ->on('nexopos_products')
                ->cascadeOnDelete();

            $table->unique(['commission_id', 'product_id'], 'unique_commission_product');
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
