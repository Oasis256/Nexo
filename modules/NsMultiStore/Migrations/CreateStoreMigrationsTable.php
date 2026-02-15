<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStoreMigrationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if (! Schema::hasTable('nexopos_stores_migrations')) {
            Schema::create('nexopos_stores_migrations', function (Blueprint $table) {
                $table->id();
                $table->integer('store_id');
                $table->string('module')->nullable();
                $table->string('file');
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
        Schema::dropIfExists('nexopos_stores_migrations');
    }
}
