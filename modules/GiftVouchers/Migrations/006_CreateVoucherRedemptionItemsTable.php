<?php
/**
 * Gift Voucher Redemption Items Table Migration
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
        Schema::createIfMissing('nexopos_gift_voucher_redemption_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('redemption_id');
            $table->unsignedBigInteger('voucher_item_id');
            $table->unsignedBigInteger('order_product_id')->nullable(); // Linked order product
            $table->decimal('quantity_redeemed', 10, 5)->default(0);
            $table->decimal('value_redeemed', 18, 5)->default(0);
            $table->unsignedBigInteger('service_provider_id')->nullable(); // User who earns commission
            $table->timestamps();

            $table->foreign('redemption_id')
                ->references('id')
                ->on('nexopos_gift_voucher_redemptions')
                ->onDelete('cascade');

            $table->foreign('voucher_item_id')
                ->references('id')
                ->on('nexopos_gift_voucher_items')
                ->onDelete('cascade');

            $table->index('redemption_id');
            $table->index('voucher_item_id');
            $table->index('service_provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_gift_voucher_redemption_items');
    }
};
