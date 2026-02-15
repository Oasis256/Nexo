<?php

/**
 * Table Migration
 * @package 5.2.6
 **/

namespace Modules\NsMultiStore\Migrations;

use App\Services\Helper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function hasUniqueIndex( $table, $indexName )
	{
		$databaseName = DB::getDatabaseName();

		return DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
	}

	/**
	 * Run the migrations.
	 */
	public function up()
	{
		Schema::table("nexopos_users", function (Blueprint $table) {
			if ( Helper::tableHasIndex( 'nexopos_users', 'nexopos_users_email_unique' ) ) {
				$table->dropUnique("nexopos_users_email_unique");
			}
		});

		Schema::table("nexopos_users", function (Blueprint $table) {
			if ( ! Helper::tableHasIndex( 'nexopos_users', 'email_origin_store_id' ) ) {
				$table->unique(["email", "origin_store_id"], "email_origin_store_id");
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table("nexopos_users", function (Blueprint $table) {
			$table->dropUnique("email_origin_store_id");
		});

		Schema::table("nexopos_users", function (Blueprint $table) {
			$table->unique(["email"], "nexopos_users_email_unique");
		});
	}
};
