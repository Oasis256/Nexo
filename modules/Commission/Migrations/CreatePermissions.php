<?php

namespace Modules\Commission\Migrations;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Permission definitions for the Commission module
     */
    protected array $permissions = [
        // Commission CRUD
        ['namespace' => 'commission.create', 'name' => 'Create Commission Definitions', 'description' => 'Can create commission definitions'],
        ['namespace' => 'commission.read', 'name' => 'View Commission Definitions', 'description' => 'Can view commission definitions'],
        ['namespace' => 'commission.update', 'name' => 'Update Commission Definitions', 'description' => 'Can update commission definitions'],
        ['namespace' => 'commission.delete', 'name' => 'Delete Commission Definitions', 'description' => 'Can delete commission definitions'],
        
        // Earned Commission CRUD
        ['namespace' => 'commission.earnings.read', 'name' => 'View Commission Earnings', 'description' => 'Can view earned commission records'],
        ['namespace' => 'commission.earnings.delete', 'name' => 'Delete Commission Earnings', 'description' => 'Can delete earned commission records'],
        
        // Dashboard & Reports
        ['namespace' => 'commission.dashboard', 'name' => 'Access Commission Dashboard', 'description' => 'Can access the commission dashboard'],
        ['namespace' => 'commission.reports', 'name' => 'View Commission Reports', 'description' => 'Can view commission reports'],
        ['namespace' => 'commission.export', 'name' => 'Export Commission Data', 'description' => 'Can export commission data for payroll'],
        
        // POS Integration
        ['namespace' => 'commission.pos.assign', 'name' => 'Assign Commission Users in POS', 'description' => 'Can assign commission earners in POS'],
        
        // Settings
        ['namespace' => 'commission.settings', 'name' => 'Manage Commission Settings', 'description' => 'Can manage commission module settings'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create permissions
        foreach ($this->permissions as $permissionData) {
            $permission = Permission::firstOrNew(['namespace' => $permissionData['namespace']]);
            $permission->name = $permissionData['name'];
            $permission->description = $permissionData['description'];
            $permission->save();
        }

        // Assign all permissions to admin role
        $admin = Role::namespace('admin');
        if ($admin) {
            $permissionNamespaces = array_column($this->permissions, 'namespace');
            $admin->addPermissions($permissionNamespaces);
        }

        // Assign read/dashboard permissions to store admin
        $storeAdmin = Role::namespace('nexopos.store.administrator');
        if ($storeAdmin) {
            $storeAdmin->addPermissions([
                'commission.read',
                'commission.earnings.read',
                'commission.dashboard',
                'commission.reports',
                'commission.export',
                'commission.pos.assign',
            ]);
        }

        // Assign minimal permissions to cashier
        $cashier = Role::namespace('nexopos.store.cashier');
        if ($cashier) {
            $cashier->addPermissions([
                'commission.pos.assign',
                'commission.earnings.read',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->permissions as $permissionData) {
            $permission = Permission::where('namespace', $permissionData['namespace'])->first();
            if ($permission) {
                $permission->removeFromRoles();
                $permission->delete();
            }
        }
    }
};
