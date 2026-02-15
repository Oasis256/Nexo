<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class UpdateAddNewPermissionsOct2521 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $permission = Permission::namespace('ns.multistore.access.root');

        if (! $permission instanceof Permission) {
            $permission = new Permission;
            $permission->namespace = 'ns.multistore.access.root';
            $permission->name = __m('Access MultiStore Root', 'NsMultiStore');
            $permission->description = __m('Allow the users to access the multistore root', 'NsMultiStore');
            $permission->save();
        }

        Role::namespace('admin')->addPermissions($permission);
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        $permission = Permission::namespace('ns.multistore.access.root');

        if ($permission instanceof Permission) {
            $permission->removeFromRoles();
            $permission->delete();
        }
    }
}
