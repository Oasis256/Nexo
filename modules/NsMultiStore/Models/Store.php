<?php

namespace Modules\NsMultiStore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\NsMultiStore\Events\StoreAfterDeletedEvent;
use Modules\NsMultiStore\Events\StoreAfterUpdatedEvent;

class Store extends Model
{
    use HasFactory;

    const STATUS_BUILDING = 'building';

    const STATUS_OPENED = 'opened';

    const STATUS_CLOSED = 'closed';

    const STATUS_DISMANTLING = 'dismantling';

    const STATUS_FAILED = 'error';

    protected $table = 'nexopos_stores';

    protected $dispatchesEvents  =   [
        'updated'   =>  StoreAfterUpdatedEvent::class,
        'deleted'   =>  StoreAfterDeletedEvent::class
    ];

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public static function switchTo($identifier)
    {
        if ($identifier instanceof self) {
            ns()->store->setStore($identifier);
        } else {
            $store = self::findOrFail($identifier);
            ns()->store->setStore($store);
        }
    }

    public static function current()
    {
        return ns()->store->current();
    }
}
