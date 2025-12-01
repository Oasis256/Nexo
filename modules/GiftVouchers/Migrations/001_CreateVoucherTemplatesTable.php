<?php
/**
 * Gift Voucher Templates Table Migration
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
        Schema::createIfMissing('nexopos_gift_voucher_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('validity_days')->default(90);
            $table->boolean('is_transferable')->default(true);
            $table->string('status')->default('active'); // active, inactive
            $table->decimal('total_value', 18, 5)->default(0);
            $table->integer('author')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('author');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_gift_voucher_templates');
    }
};
