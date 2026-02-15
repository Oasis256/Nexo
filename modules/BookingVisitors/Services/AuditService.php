<?php

namespace Modules\BookingVisitors\Services;

use Modules\BookingVisitors\Models\AuditLog;
use Modules\BookingVisitors\Support\StoreContext;

class AuditService
{
    public function log(string $action, string $entityType, ?int $entityId, array $payload = [], ?int $actorId = null): void
    {
        AuditLog::query()->create([
            'store_id' => StoreContext::id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'payload' => $payload,
            'author' => $actorId,
        ]);
    }
}

