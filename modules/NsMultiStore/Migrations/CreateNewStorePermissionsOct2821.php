<?php
/**
 * Table Migration
**/

namespace Modules\NsMultiStore\Migrations;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

class CreateNewStorePermissionsOct2821 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $userCreation = Permission::namespace('ns.multistore.create-users');
        if (! $userCreation instanceof Permission) {
            $userCreation = new Permission;
            $userCreation->namespace = 'ns.multistore.create-users';
            $userCreation->name = __m('Create Sub Store Users', 'NsMultiStore');
            $userCreation->description = __m('Allow users creation on sub stores.', 'NsMultiStore');
            $userCreation->save();
        }

        $deleteUser = Permission::namespace('ns.multistore.delete-users');
        if (! $deleteUser instanceof Permission) {
            $deleteUser = new Permission;
            $deleteUser->namespace = 'ns.multistore.delete-users';
            $deleteUser->name = __m('Delete Sub Store Users', 'NsMultiStore');
            $deleteUser->description = __m('Allow users deletion on sub stores.', 'NsMultiStore');
            $deleteUser->save();
        }

        $updateUser = Permission::namespace('ns.multistore.update-users');
        if (! $updateUser instanceof Permission) {
            $updateUser = new Permission;
            $updateUser->namespace = 'ns.multistore.update-users';
            $updateUser->name = __m('Update Sub Store Users', 'NsMultiStore');
            $updateUser->description = __m('Allow to update existing users on sub stores.', 'NsMultiStore');
            $updateUser->save();
        }

        $readUser = Permission::namespace('ns.multistore.read-users');
        if (! $readUser instanceof Permission) {
            $readUser = new Permission;
            $readUser->namespace = 'ns.multistore.read-users';
            $readUser->name = __m('Read Sub Store Users', 'NsMultiStore');
            $readUser->description = __m('Allow to see available users on sub stores.', 'NsMultiStore');
            $readUser->save();
        }

        Role::namespace(Role::ADMIN)->addPermissions([
            $userCreation,
            $deleteUser,
            $updateUser,
            $readUser,
        ]);

        Role::namespace(Role::STOREADMIN)->addPermissions([
            $userCreation,
            $deleteUser,
            $updateUser,
            $readUser,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        // drop tables here
    }
}
