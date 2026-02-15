<?php
/**
 * Gift Vouchers Table Migration
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
        Schema::createIfMissing('nexopos_gift_vouchers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 32)->unique(); // Human-readable code (e.g., GV-A8K3M2X9)
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('purchaser_id')->nullable(); // Customer who bought the voucher
            $table->unsignedBigInteger('purchase_order_id')->nullable(); // Order when voucher was purchased
            $table->decimal('total_value', 18, 5)->default(0);
            $table->decimal('remaining_value', 18, 5)->default(0);
            $table->decimal('points_awarded', 18, 5)->default(0); // Loyalty points given to purchaser
            $table->string('status')->default('active'); // active, partially_redeemed, fully_redeemed, expired, cancelled
            $table->datetime('expires_at')->nullable();
            
            // QR Code fields
            $table->string('qr_redemption_key', 128)->unique()->nullable(); // Secure key for QR redemption
            $table->datetime('qr_key_expires_at')->nullable();
            $table->string('qr_image_path', 255)->nullable();
            
            // Accounting
            $table->unsignedBigInteger('deferred_transaction_id')->nullable(); // Link to liability transaction
            
            $table->integer('author')->nullable();
            $table->timestamps();

            $table->index('code');
            $table->index('status');
            $table->index('purchaser_id');
            $table->index('purchase_order_id');
            $table->index('qr_redemption_key');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_gift_vouchers');
    }
};
