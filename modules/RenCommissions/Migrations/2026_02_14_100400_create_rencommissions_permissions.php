<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Modules\RenCommissions\Support\PermissionsRegistry as RenCommissionsPermissions;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = RenCommissionsPermissions::all();

        foreach ($permissions as $entry) {
            $permission = Permission::firstOrNew(['namespace' => $entry['namespace']]);
            $permission->name = $entry['name'];
            $permission->description = $entry['description'];
            $permission->namespace = $entry['namespace'];
            $permission->save();
        }

        $admin = Role::namespace(Role::ADMIN);
        if ($admin) {
            $admin->addPermissions(RenCommissionsPermissions::namespaces(), silent: true);
        }
    }

    public function down(): void
    {
        Permission::whereIn('namespace', RenCommissionsPermissions::namespaces())->delete();
    }
};
