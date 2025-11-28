<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('skeleton_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('category')->default('general');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('skeleton_items');
    }
};
