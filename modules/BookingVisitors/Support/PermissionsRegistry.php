<?php

namespace Modules\BookingVisitors\Support;

class PermissionsRegistry
{
    public static function all(): array
    {
        $permissions = include __DIR__ . '/../Config/Permissions.php';

        return is_array($permissions) ? $permissions : [];
    }

    public static function namespaces(): array
    {
        return array_column(self::all(), 'namespace');
    }
}

