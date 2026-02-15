<?php
/**
 * Table Migration
**/

namespace Modules\MyNexoPOS\Migrations;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class CreateMynexoposPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $permission = Permission::namespace('mns.update-system');

        if (! $permission instanceof Permission) {
            $permission = new Permission;
            $permission->namespace = 'mns.update-system';
            $permission->name = __m('Update The System', 'MyNexoPOS');
            $permission->description = __m('Give the capacity to update the system.', 'MyNexoPOS');
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
        $permission = Permission::namespace('mns.update-system');

        if ( $permission instanceof Permission ) {
            Role::namespace( Role::ADMIN )->removePermissions($permission->namespace);
            $permission->delete();
        }

    }
}
