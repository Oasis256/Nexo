<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if (! Schema::hasTable('nexopos_stores')) {
            Schema::create('nexopos_stores', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->integer('author');
                $table->text('description')->nullable();
                $table->string('thumb')->nullable();
                $table->string('status')->default(1);
                $table->string('roles_id')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists('nexopos_stores');
    }
}
