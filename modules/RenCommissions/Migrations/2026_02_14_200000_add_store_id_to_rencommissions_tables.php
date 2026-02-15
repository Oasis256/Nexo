<?php

use App\Services\Helper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rencommissions_types') && !Schema::hasColumn('rencommissions_types', 'store_id')) {
            Schema::table('rencommissions_types', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('id');
                $table->index(['store_id', 'is_active', 'priority'], 'rc_types_store_active_priority_idx');
            });
        }

        if (Schema::hasTable('rencommissions_pos_sessions') && !Schema::hasColumn('rencommissions_pos_sessions', 'store_id')) {
            Schema::table('rencommissions_pos_sessions', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('id');
                if (Helper::tableHasIndex('rencommissions_pos_sessions', 'rencommissions_pos_sessions_session_id_product_index_unique')) {
                    $table->dropUnique(['session_id', 'product_index']);
                }
                if (Helper::tableHasIndex('rencommissions_pos_sessions', 'rencommissions_pos_sessions_session_id_index')) {
                    $table->dropIndex(['session_id']);
                }
                $table->unique(['store_id', 'session_id', 'product_index'], 'rc_pos_session_unique');
                $table->index(['store_id', 'session_id'], 'rc_pos_session_idx');
            });
        }

        if (Schema::hasTable('rencommissions_payouts') && !Schema::hasColumn('rencommissions_payouts', 'store_id')) {
            Schema::table('rencommissions_payouts', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('id');
                if (Helper::tableHasIndex('rencommissions_payouts', 'rencommissions_payouts_status_created_at_index')) {
                    $table->dropIndex(['status', 'created_at']);
                }
                $table->index(['store_id', 'status', 'created_at'], 'rc_payout_store_status_created_idx');
            });
        }

        if (Schema::hasTable('rencommissions_order_item_commissions') && !Schema::hasColumn('rencommissions_order_item_commissions', 'store_id')) {
            Schema::table('rencommissions_order_item_commissions', function (Blueprint $table) {
                $table->unsignedBigInteger('store_id')->nullable()->after('id');
                if (Helper::tableHasIndex('rencommissions_order_item_commissions', 'rc_oic_order_product_idx')) {
                    $table->dropIndex('rc_oic_order_product_idx');
                } elseif (Helper::tableHasIndex('rencommissions_order_item_commissions', 'rencommissions_order_item_commissions_order_id_order_product_id_index')) {
                    $table->dropIndex(['order_id', 'order_product_id']);
                }

                if (Helper::tableHasIndex('rencommissions_order_item_commissions', 'rc_oic_status_created_idx')) {
                    $table->dropIndex('rc_oic_status_created_idx');
                } elseif (Helper::tableHasIndex('rencommissions_order_item_commissions', 'rencommissions_order_item_commissions_status_created_at_index')) {
                    $table->dropIndex(['status', 'created_at']);
                }
                $table->index(['store_id', 'order_id', 'order_product_id'], 'rc_oic_store_order_product_idx');
                $table->index(['store_id', 'status', 'created_at'], 'rc_oic_store_status_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rencommissions_order_item_commissions') && Schema::hasColumn('rencommissions_order_item_commissions', 'store_id')) {
            Schema::table('rencommissions_order_item_commissions', function (Blueprint $table) {
                $table->dropIndex('rc_oic_store_order_product_idx');
                $table->dropIndex('rc_oic_store_status_created_idx');
                $table->index(['order_id', 'order_product_id'], 'rc_oic_order_product_idx');
                $table->index(['status', 'created_at'], 'rc_oic_status_created_idx');
                $table->dropColumn('store_id');
            });
        }

        if (Schema::hasTable('rencommissions_payouts') && Schema::hasColumn('rencommissions_payouts', 'store_id')) {
            Schema::table('rencommissions_payouts', function (Blueprint $table) {
                $table->dropIndex('rc_payout_store_status_created_idx');
                $table->index(['status', 'created_at']);
                $table->dropColumn('store_id');
            });
        }

        if (Schema::hasTable('rencommissions_pos_sessions') && Schema::hasColumn('rencommissions_pos_sessions', 'store_id')) {
            Schema::table('rencommissions_pos_sessions', function (Blueprint $table) {
                $table->dropUnique('rc_pos_session_unique');
                $table->dropIndex('rc_pos_session_idx');
                $table->unique(['session_id', 'product_index']);
                $table->index(['session_id']);
                $table->dropColumn('store_id');
            });
        }

        if (Schema::hasTable('rencommissions_types') && Schema::hasColumn('rencommissions_types', 'store_id')) {
            Schema::table('rencommissions_types', function (Blueprint $table) {
                $table->dropIndex('rc_types_store_active_priority_idx');
                $table->dropColumn('store_id');
                $table->index(['is_active', 'priority']);
            });
        }
    }
};
