<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nexopos_products') && !Schema::hasColumn('nexopos_products', 'pinned')) {
            Schema::table('nexopos_products', function (Blueprint $table) {
                $table->boolean('pinned')->default(false)->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nexopos_products') && Schema::hasColumn('nexopos_products', 'pinned')) {
            Schema::table('nexopos_products', function (Blueprint $table) {
                $table->dropColumn('pinned');
            });
        }
    }
};
