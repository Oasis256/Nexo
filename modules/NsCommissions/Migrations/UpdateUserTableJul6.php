<?php
/**
 * Table Migration
**/

namespace Modules\NsCommissions\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserTableJul6 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::table('nexopos_users', function (Blueprint $table) {
            if (Schema::hasColumn('nexopos_users', 'current_commissions')) {
                $table->float('current_commissions')->default(0);
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
        Schema::table('nexopos_users', function (Blueprint $table) {
            if (Schema::hasColumn('nexopos_users', 'current_commissions')) {
                $table->float('current_commissions')->default(0);
            }
        });
    }
}
