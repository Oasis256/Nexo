<?php
/**
 * Gift Voucher Template Items Table Migration
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Migrations;

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
        Schema::createIfMissing('nexopos_gift_voucher_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('unit_id');
            $table->decimal('quantity', 10, 5)->default(1);
            $table->decimal('unit_price', 18, 5)->default(0);
            $table->decimal('total_price', 18, 5)->default(0);
            $table->decimal('commission_rate', 8, 5)->default(0);
            $table->string('commission_type')->default('percentage'); // percentage, fixed
            $table->timestamps();

            $table->foreign('template_id')
                ->references('id')
                ->on('nexopos_gift_voucher_templates')
                ->onDelete('cascade');

            $table->index('template_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_gift_voucher_template_items');
    }
};
