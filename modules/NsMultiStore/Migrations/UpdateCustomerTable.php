<?php

/**
 * Table Migration
 * @package 5.2.2
 **/

namespace Modules\NsMultiStore\Migrations;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		if ( Schema::hasTable( 'nexopos_users' ) ) {
			Schema::table( 'nexopos_users', function ( Blueprint $table ) {
				if ( ! Schema::hasColumn( 'nexopos_users', 'origin_store_id' ) ) {
					$table->unsignedBigInteger( 'origin_store_id' )->nullable();
				}
			});		
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		if ( Schema::hasTable( 'nexopos_users' ) ) {
			Schema::table( 'nexopos_users', function ( Blueprint $table ) {
				$table->dropColumn( 'origin_store_id' );
			});
		}
	}
};
