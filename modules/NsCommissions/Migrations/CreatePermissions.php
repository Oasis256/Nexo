<?php
/**
 * Table Migration
**/

namespace Modules\NsCommissions\Migrations;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class CreatePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $permId = 'ns.commissions-create';
        $createCommissions = Permission::namespace($permId);

        if (! $createCommissions instanceof Permission) {
            $createCommissions = new Permission;
            $createCommissions->namespace = $permId;
            $createCommissions->name = __m('Create Commissions', 'NsCommissions');
            $createCommissions->description = __m('Allow the users to create commissions.', 'NsCommissions');
            $createCommissions->save();
        }

        $permId = 'ns.commissions-delete';
        $deleteCommissions = Permission::namespace($permId);

        if (! $deleteCommissions instanceof Permission) {
            $deleteCommissions = new Permission;
            $deleteCommissions->namespace = $permId;
            $deleteCommissions->name = __m('Delete Commissions', 'NsCommissions');
            $deleteCommissions->description = __m('Allow the users to delete commissions.', 'NsCommissions');
            $deleteCommissions->save();
        }

        $permId = 'ns.commissions-update';
        $updateCommissions = Permission::namespace($permId);

        if (! $updateCommissions instanceof Permission) {
            $updateCommissions = new Permission;
            $updateCommissions->namespace = $permId;
            $updateCommissions->name = __m('Update Commissions', 'NsCommissions');
            $updateCommissions->description = __m('Allow the users to update commissions.', 'NsCommissions');
            $updateCommissions->save();
        }

        $permId = 'ns.commissions-read';
        $readCommissions = Permission::namespace($permId);

        if (! $readCommissions instanceof Permission) {
            $readCommissions = new Permission;
            $readCommissions->namespace = $permId;
            $readCommissions->name = __m('Read Commissions', 'NsCommissions');
            $readCommissions->description = __m('Allow the users to read commissions.', 'NsCommissions');
            $readCommissions->save();
        }

        $permId = 'ns.commissions-reports';
        $readCommissionsReport = Permission::namespace($permId);

        if (! $readCommissionsReport instanceof Permission) {
            $readCommissionsReport = new Permission;
            $readCommissionsReport->namespace = $permId;
            $readCommissionsReport->name = __m('Read Commissions Reports', 'NsCommissions');
            $readCommissionsReport->description = __m('Allow the user to read the commissions report.', 'NsCommissions');
            $readCommissionsReport->save();
        }

        $permId = 'ns.commissions-settings';
        $changeCommission = Permission::namespace($permId);

        if (! $changeCommission instanceof Permission) {
            $changeCommission = new Permission;
            $changeCommission->namespace = $permId;
            $changeCommission->name = __m('Change Commissions', 'NsCommissions');
            $changeCommission->description = __m('Allow the user to read the commissions report.', 'NsCommissions');
            $changeCommission->save();
        }

        Role::namespace('admin')->addPermissions([
            $createCommissions,
            $deleteCommissions,
            $updateCommissions,
            $readCommissions,
            $readCommissionsReport,
            $changeCommission,
        ]);

        Role::namespace('nexopos.store.administrator')->addPermissions([
            $createCommissions,
            $deleteCommissions,
            $updateCommissions,
            $readCommissions,
            $readCommissionsReport,
            $changeCommission,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        $permissions = [
            'ns.commissions-create',
            'ns.commissions-delete',
            'ns.commissions-update',
            'ns.commissions-read',
            'ns.commissions-reports',
        ];

        foreach ($permissions as $permission) {
            $permission = Permission::find($permission);

            if ($permission instanceof Permission) {
                $permission->removeFromRoles();
                $permission->delete();
            }
        }
    }
}
