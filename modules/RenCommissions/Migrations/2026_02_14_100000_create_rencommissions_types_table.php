<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rencommissions_types')) {
            Schema::create('rencommissions_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_id')->nullable();
                $table->string('name', 100);
                $table->string('description', 255)->nullable();
                $table->enum('calculation_method', ['percentage', 'fixed', 'on_the_house'])->default('fixed');
                $table->decimal('default_value', 10, 2)->default(0);
                $table->decimal('min_value', 10, 2)->nullable();
                $table->decimal('max_value', 10, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('priority')->default(0);
                $table->unsignedBigInteger('author')->nullable();
                $table->timestamps();
                $table->index(['store_id', 'is_active', 'priority']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rencommissions_types');
    }
};
