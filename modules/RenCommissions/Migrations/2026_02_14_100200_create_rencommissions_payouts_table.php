<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rencommissions_payouts')) {
            Schema::create('rencommissions_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('reference', 100)->unique();
                $table->dateTime('period_start')->nullable();
                $table->dateTime('period_end')->nullable();
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->unsignedInteger('entries_count')->default(0);
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'status', 'created_at'], 'rc_payout_store_status_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rencommissions_payouts');
    }
};
