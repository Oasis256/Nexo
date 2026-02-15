<?php

/**
 * Table Migration
 * @package 5.3.3
 **/

namespace Modules\NsMultiStore\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up()
	{
		Schema::createIfMissing( 'nexopos_stores_roles', function( Blueprint $table ) {
			$table->bigIncrements( 'id' );
			$table->integer( 'role_id' );
			$table->integer( 'store_id' );

			// we need to ensure there is only unique combinaison of role_id and store_id
			$table->unique( [ 'role_id', 'store_id' ] );
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists( 'nexopos_stores_roles' );
	}
};
