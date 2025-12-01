<?php
/**
 * Gift Voucher Commissions Table Migration
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
        Schema::createIfMissing('nexopos_gift_voucher_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('redemption_item_id');
            $table->unsignedBigInteger('voucher_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('order_product_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id'); // Commission earner
            $table->decimal('base_amount', 18, 5)->default(0); // Calculation base
            $table->decimal('commission_rate', 8, 5)->default(0);
            $table->string('commission_type')->default('percentage'); // percentage, fixed
            $table->decimal('value', 18, 5)->default(0); // Earned commission
            $table->integer('author')->nullable();
            $table->timestamps();

            $table->foreign('redemption_item_id')
                ->references('id')
                ->on('nexopos_gift_voucher_redemption_items')
                ->onDelete('cascade');

            $table->index('voucher_id');
            $table->index('user_id');
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_gift_voucher_commissions');
    }
};
