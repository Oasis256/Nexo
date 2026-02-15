<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateAddRoleLimitationPerStore extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Schema::table('nexopos_stores', function (Blueprint $table) {
            if (! Schema::hasColumn('nexopos_stores', 'roles_id')) {
                $table->string('roles_id')->nullable();
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
        Schema::table('nexopos_stores', function (Blueprint $table) {
            if (Schema::hasColumn('nexopos_stores', 'roles_id')) {
                $table->dropColumn('roles_id');
            }
        });
    }
}
