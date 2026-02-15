<?php

namespace Modules\NsCommissions\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nexopos_commissions', function (Blueprint $table) {
            // Add calculation_base column for percentage calculations
            if (!Schema::hasColumn('nexopos_commissions', 'calculation_base')) {
                $table->string('calculation_base', 20)->default('gross')->after('type');
            }
        });

        // Update type values: standardize 'flat' to 'fixed' if any exist
        DB::table('nexopos_commissions')
            ->where('type', 'flat')
            ->update(['type' => 'fixed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nexopos_commissions', function (Blueprint $table) {
            if (Schema::hasColumn('nexopos_commissions', 'calculation_base')) {
                $table->dropColumn('calculation_base');
            }
        });
    }
};
