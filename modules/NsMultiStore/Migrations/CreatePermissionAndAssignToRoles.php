<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionAndAssignToRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $permission = Permission::namespace('ns.multistore.create.stores');
        if (! $permission instanceof Permission) {
            $permission = new Permission;
            $permission->namespace = 'ns.multistore.create.stores';
            $permission->name = __m('Create Store', 'NsMultiStore');
            $permission->description = __m('Allow users to create stores.', 'NsMultiStore');
            $permission->save();
        }

        $permission = Permission::namespace('ns.multistore.read.stores');
        if (! $permission instanceof Permission) {
            $permission = new Permission;
            $permission->namespace = 'ns.multistore.read.stores';
            $permission->name = __m('Access Store', 'NsMultiStore');
            $permission->description = __m('Allow users to access stores.', 'NsMultiStore');
            $permission->save();
        }

        $permission = Permission::namespace('ns.multistore.update.stores');
        if (! $permission instanceof Permission) {
            $permission = new Permission;
            $permission->namespace = 'ns.multistore.update.stores';
            $permission->name = __m('Update Store', 'NsMultiStore');
            $permission->description = __m('Allow users to update stores.', 'NsMultiStore');
            $permission->save();
        }

        $permission = Permission::namespace('ns.multistore.delete.stores');
        if (! $permission instanceof Permission) {
            $permission = new Permission;
            $permission->namespace = 'ns.multistore.delete.stores';
            $permission->name = __m('Delete Store', 'NsMultiStore');
            $permission->description = __m('Allow users to delete stores.', 'NsMultiStore');
            $permission->save();
        }

        Role::namespace('admin')->addPermissions([
            'ns.multistore.create.stores',
            'ns.multistore.read.stores',
            'ns.multistore.update.stores',
            'ns.multistore.delete.stores',
        ]);

        Role::namespace('nexopos.store.administrator')->addPermissions([
            'ns.multistore.create.stores',
            'ns.multistore.read.stores',
            'ns.multistore.update.stores',
            'ns.multistore.delete.stores',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        $permission = Permission::namespace('ns.multistore.create.stores');
        if ($permission instanceof Permission) {
            $permission->removeFromRoles();
        }

        $permission = Permission::namespace('ns.multistore.read.stores');
        if ($permission instanceof Permission) {
            $permission->removeFromRoles();
        }

        $permission = Permission::namespace('ns.multistore.update.stores');
        if ($permission instanceof Permission) {
            $permission->removeFromRoles();
        }

        $permission = Permission::namespace('ns.multistore.delete.stores');
        if ($permission instanceof Permission) {
            $permission->removeFromRoles();
        }
    }
}
