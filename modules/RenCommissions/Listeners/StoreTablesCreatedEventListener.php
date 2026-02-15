<?php

namespace Modules\RenCommissions\Listeners;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StoreTablesCreatedEventListener
{
    public function handle(object $event): void
    {
        if (!isset($event->store) || !isset($event->store->id)) {
            return;
        }

        $tableName = 'store_' . $event->store->id . '_nexopos_products';

        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'commission_value')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->decimal('commission_value', 10, 2)->default(0);
        });
    }
}

