<?php

namespace Modules\BookingVisitors\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class StoreContext
{
    public static function id(): ?int
    {
        if (function_exists('ns') && isset(ns()->store) && method_exists(ns()->store, 'getCurrentStore')) {
            $store = ns()->store->getCurrentStore();
            if ($store) {
                return (int) $store->id;
            }
        }

        return null;
    }

    public static function apply(EloquentBuilder|QueryBuilder $query, string $column = 'store_id'): EloquentBuilder|QueryBuilder
    {
        $storeId = self::id();
        if ($storeId === null) {
            return $query;
        }

        return $query->where($column, $storeId);
    }
}

