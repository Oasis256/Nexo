<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRoleTable23Dec21 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::table('nexopos_roles', function (Blueprint $table) {
            if (! Schema::hasColumn('nexopos_roles', 'total_stores')) {
                $table->integer('total_stores')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        Schema::table('nexopos_roles', function (Blueprint $table) {
            if (Schema::hasColumn('nexopos_roles', 'total_stores')) {
                $table->dropColumn('total_stores')->default(0);
            }
        });
    }
}
