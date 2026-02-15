<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMultiStoreDashboardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        if (! Schema::hasTable('nexopos_multistore_dashboard')) {
            Schema::create('nexopos_multistore_dashboard', function (Blueprint $table) {
                $table->id();
                $table->float('total_unpaid_orders')->default(0);
                $table->float('day_unpaid_orders')->default(0);
                $table->float('total_unpaid_orders_count')->default(0);
                $table->float('day_unpaid_orders_count')->default(0);
                $table->float('total_paid_orders')->default(0);
                $table->float('day_paid_orders')->default(0);
                $table->float('total_paid_orders_count')->default(0);
                $table->float('day_paid_orders_count')->default(0);
                $table->float('total_partially_paid_orders')->default(0);
                $table->float('day_partially_paid_orders')->default(0);
                $table->float('total_partially_paid_orders_count')->default(0);
                $table->float('day_partially_paid_orders_count')->default(0);
                $table->float('total_income')->default(0);
                $table->float('day_income')->default(0);
                $table->float('total_discounts')->default(0);
                $table->float('day_discounts')->default(0);
                $table->float('day_taxes')->default(0);
                $table->float('total_taxes')->default(0);
                $table->float('total_wasted_goods_count')->default(0);
                $table->float('day_wasted_goods_count')->default(0);
                $table->float('total_wasted_goods')->default(0);
                $table->float('day_wasted_goods')->default(0);
                $table->float('total_transactions')->default(0);
                $table->float('day_transactions')->default(0);
                $table->integer('total_stores')->default(0);
                $table->integer('day_stores')->default(0);
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
        Schema::dropIfExists('nexopos_multistore_dashboard');
    }
}
