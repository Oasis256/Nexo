<?php
/**
 * Table Migration
**/

namespace Modules\NsCommissions\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTablesJul6 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::createIfMissing('nexopos_orders_commissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->float('value')->default(0);
            $table->integer('user_id');
            $table->integer('order_id');
            $table->integer('author');
            $table->integer('commission_id');
            $table->timestamps();
        });

        Schema::createIfMissing('nexopos_commissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->boolean('active')->default(false);
            $table->string('type')->default('percentage'); // flat | percentage
            $table->float('value')->default(0); //
            $table->integer('role_id');
            $table->integer('author');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::dropIfExists('nexopos_orders_commissions');
        Schema::dropIfExists('nexopos_commissions');
    }
}
