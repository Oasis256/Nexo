<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use App\Models\Migration;

class UpdateExistingMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        Migration::where('migration', '2014_10_13_000000_create_tendoo_users_table')->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        // drop tables here
    }
}
