<?php

/**
 * Skeleton Module Permissions
 * 
 * This file defines all permissions for the Skeleton module.
 * Permissions follow the pattern: {module}.{action}.{resource}
 */

use App\Models\Permission;

if (defined('NEXO_CREATE_PERMISSIONS')) {
    $permissions = [
        'skeleton.create.items' => [
            'name' => 'Create Skeleton Items',
            'description' => 'Allow creating new items in the Skeleton module',
        ],
        'skeleton.read.items' => [
            'name' => 'Read Skeleton Items',
            'description' => 'Allow viewing items and pages in the Skeleton module',
        ],
        'skeleton.update.items' => [
            'name' => 'Update Skeleton Items',
            'description' => 'Allow editing items and settings in the Skeleton module',
        ],
        'skeleton.delete.items' => [
            'name' => 'Delete Skeleton Items',
            'description' => 'Allow deleting items in the Skeleton module',
        ],
    ];

    foreach ($permissions as $namespace => $data) {
        $permission = Permission::firstOrNew(['namespace' => $namespace]);
        $permission->name = $data['name'];
        $permission->namespace = $namespace;
        $permission->description = $data['description'];
        $permission->save();
    }
}
