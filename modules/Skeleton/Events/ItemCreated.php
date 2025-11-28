<?php

namespace Modules\Skeleton\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Skeleton\Models\SkeletonItem;

class ItemCreated
{
    use Dispatchable, SerializesModels;

    public $item;

    public function __construct(SkeletonItem $item)
    {
        $this->item = $item;
    }
}
