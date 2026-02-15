<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Define constant to trigger permission registration
        if (!defined('NEXO_CREATE_PERMISSIONS')) {
            define('NEXO_CREATE_PERMISSIONS', true);
        }

        // Register module-specific permissions
        $permissions = [
            ['namespace' => 'rencommissions.create.types', 'name' => 'Create Commission Types', 'description' => 'Can create commission types'],
            ['namespace' => 'rencommissions.read.types', 'name' => 'Read Commission Types', 'description' => 'Can view commission types'],
            ['namespace' => 'rencommissions.update.types', 'name' => 'Update Commission Types', 'description' => 'Can update commission types'],
            ['namespace' => 'rencommissions.delete.types', 'name' => 'Delete Commission Types', 'description' => 'Can delete commission types'],
            ['namespace' => 'rencommissions.read.commissions', 'name' => 'Read Commissions', 'description' => 'Can view commission records'],
            ['namespace' => 'rencommissions.update.commissions', 'name' => 'Update Commissions', 'description' => 'Can update commission records (void, mark paid)'],
            ['namespace' => 'rencommissions.manage.payouts', 'name' => 'Manage Payouts', 'description' => 'Can manage commission payouts'],
            ['namespace' => 'rencommissions.read.own', 'name' => 'Read Own Commissions', 'description' => 'Can view own commission records'],
            ['namespace' => 'rencommissions.earn.commissions', 'name' => 'Earn Commission', 'description' => 'Can earn commission on eligible sales'],
            ['namespace' => 'rencommissions.read.reports', 'name' => 'Read Commission Reports', 'description' => 'Can view commission reports'],
            ['namespace' => 'rencommissions.admin', 'name' => 'Commission Admin', 'description' => 'Full admin access to commission system'],
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrNew(['namespace' => $perm['namespace']]);
            $permission->name = $perm['name'];
            $permission->namespace = $perm['namespace'];
            $permission->description = $perm['description'];
            $permission->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove module-specific permissions
        $namespaces = [
            'rencommissions.create.types',
            'rencommissions.read.types',
            'rencommissions.update.types',
            'rencommissions.delete.types',
            'rencommissions.read.commissions',
            'rencommissions.update.commissions',
            'rencommissions.manage.payouts',
            'rencommissions.read.own',
            'rencommissions.earn.commissions',
            'rencommissions.read.reports',
            'rencommissions.admin',
        ];
        foreach ($namespaces as $namespace) {
            Permission::where('namespace', $namespace)->delete();
        }
    }
};
