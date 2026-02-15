<?php
/**
 * Gift Voucher Redemptions Table Migration
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
        Schema::createIfMissing('nexopos_gift_voucher_redemptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('voucher_id');
            $table->unsignedBigInteger('redeemer_id')->nullable(); // Customer redeeming (may differ from purchaser)
            $table->unsignedBigInteger('redemption_order_id')->nullable(); // Order where redemption occurred
            $table->decimal('total_value', 18, 5)->default(0); // Value redeemed in this transaction
            $table->unsignedBigInteger('revenue_transaction_id')->nullable(); // Link to revenue recognition transaction
            $table->integer('author')->nullable(); // Cashier who processed
            $table->timestamps();

            $table->foreign('voucher_id')
                ->references('id')
                ->on('nexopos_gift_vouchers')
                ->onDelete('cascade');

            $table->index('voucher_id');
            $table->index('redeemer_id');
            $table->index('redemption_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_gift_voucher_redemptions');
    }
};
