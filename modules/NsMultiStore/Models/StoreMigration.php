<?php

namespace Modules\NsMultiStore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreMigration extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'nexopos_stores_migrations';

    public function scopeForStore($query, Store $store)
    {
        return $query->where('store_id', $store->id);
    }
}
