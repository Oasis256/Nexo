<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureColumnOnTable('nexopos_products');

        if (Schema::hasTable('nexopos_stores')) {
            DB::table('nexopos_stores')->pluck('id')->each(function ($storeId) {
                $this->ensureColumnOnTable('store_' . $storeId . '_nexopos_products');
            });
        }
    }

    public function down(): void
    {
        $this->dropColumnOnTable('nexopos_products');

        if (Schema::hasTable('nexopos_stores')) {
            DB::table('nexopos_stores')->pluck('id')->each(function ($storeId) {
                $this->dropColumnOnTable('store_' . $storeId . '_nexopos_products');
            });
        }
    }

    private function ensureColumnOnTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'commission_value')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('commission_value', 10, 2)->default(0);
        });
    }

    private function dropColumnOnTable(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'commission_value')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('commission_value');
        });
    }
};

