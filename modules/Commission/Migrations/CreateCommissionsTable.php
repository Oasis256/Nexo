<?php

namespace Modules\Commission\Migrations;

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
        Schema::createIfMissing('nexopos_commissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->enum('type', ['on_the_house', 'fixed', 'percentage'])->default('percentage');
            $table->enum('calculation_base', ['fixed', 'gross', 'net'])->default('gross');
            $table->decimal('value', 18, 5)->default(0);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('author')->nullable();
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('nexopos_roles')->onDelete('set null');
            $table->foreign('author')->references('id')->on('nexopos_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nexopos_commissions');
    }
};
