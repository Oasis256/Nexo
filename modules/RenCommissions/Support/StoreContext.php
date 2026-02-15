<?php

namespace Modules\RenCommissions\Support;

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

    public static function constrain(EloquentBuilder|QueryBuilder $query, string $column = 'store_id'): EloquentBuilder|QueryBuilder
    {
        $storeId = self::id();

        if ($storeId === null) {
            return $query;
        }

        return $query->where(function ($builder) use ($column, $storeId) {
            $builder->where($column, $storeId)->orWhereNull($column);
        });
    }

    public static function applyIfPresent(EloquentBuilder|QueryBuilder $query, string $column = 'store_id'): EloquentBuilder|QueryBuilder
    {
        $storeId = self::id();

        if ($storeId === null) {
            return $query;
        }

        return $query->where($column, $storeId);
    }

    public static function matches(?int $storeId): bool
    {
        $current = self::id();

        if ($current === null) {
            return true;
        }

        if ($storeId === null) {
            return true;
        }

        return (int) $storeId === (int) $current;
    }
}
