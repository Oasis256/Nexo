<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rencommissions_pos_sessions')) {
            Schema::create('rencommissions_pos_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('session_id', 191);
                $table->unsignedInteger('product_index');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('earner_id');
                $table->unsignedBigInteger('type_id')->nullable();
                $table->enum('commission_method', ['percentage', 'fixed', 'on_the_house'])->default('fixed');
                $table->decimal('commission_value', 10, 2)->default(0);
                $table->decimal('unit_price', 10, 2)->default(0);
                $table->decimal('quantity', 10, 2)->default(1);
                $table->decimal('total_commission', 10, 2)->default(0);
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamps();

                $table->unique(['store_id', 'session_id', 'product_index'], 'rc_pos_session_unique');
                $table->index(['store_id', 'session_id'], 'rc_pos_session_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rencommissions_pos_sessions');
    }
};
