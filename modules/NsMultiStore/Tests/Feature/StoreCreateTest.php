<?php

namespace Modules\NsMultiStore\Tests\Feature;

use App\Classes\Hook;
use App\Classes\Schema;
use App\Models\Option;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\UserWidget;
use App\Services\CurrencyService;
use Faker\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\NsMultiStore\Crud\UsersCrud;
use Modules\NsMultiStore\Models\Store;
use Tests\TestCase;

class StoreCreateTest extends TestCase
{
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStore()
    {
        $user   =   Role::namespace('admin')->users->first();
        $faker  = Factory::create();

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $allRolesButAdmin   =   Role::where( 'name', '!=', 'admin' )->get()->map( fn( $role ) => $role->id )->toArray();

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', 'api/crud/ns.multistore', [
                'name'  =>  $this->faker->company(),
                'general'  => [
                    'status' => Store::STATUS_OPENED,
                    'allowed_roles' =>  $allRolesButAdmin
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success',
        ]);

        $data   =   $response->json();
        $store  =   $data[ 'data' ][ 'entry' ];

        /**
         * The response must return the id of the created store
         * with that we'll perform various other checks.
         */
        $this->assertArrayHasKey( 'id', $store );

        /**
         * We need to check if the store tables
         * has been created. We'll check all table which names starts with store_ . $store[ 'id' ] . '_'
         * if we have some result, then we're good.
         */
        $tables = Schema::getTableListing();
        $storeTables = [];

        foreach ($tables as $tableName) {
            if (strpos($tableName, 'store_' . $store['id'] . '_') === 0) {
                $storeTables[] = $tableName;
            }
        }

        $this->assertNotEmpty($storeTables);

        /**
         * No we'll perform store related operation
         */
        $password   =   $faker->password();

        $request = $this->withSession($this->app['session']->all())
            ->json('POST', 'api/store/' . $store[ 'id' ] . '/crud/' . UsersCrud::IDENTIFIER, [
                'username'  =>  $faker->username(),
                'general'  => [
                    'active' => 1,
                    'email' =>  $faker->email(),
                    'password'  =>  $password,
                    'password_confirm'  =>  $password,
                    'roles' =>  collect( $allRolesButAdmin )->random( 1 )
                ]
            ]);

        $response   =   $request->json();

        $user   =   User::find( $response[ 'data' ][ 'entry' ][ 'id' ] );

        /**
         * Step 1: We'll check if that user has the role assigned to the store.
         */
        $store  =   Store::find( $store[ 'id' ] );
        $storeRoles     =   json_decode( $store->roles_id );

        $this->assertTrue(
            $user->roles()->whereIn( 'nexopos_roles.id', $storeRoles )->count() > 0,
            'The user created does not have the role assigned to the store'
        );

        /**
         * Step 2: We'll check if the user has widgets created for him
         * On that particular store
         */
        ns()->store->setStore( $store );

        $this->assertTrue(
            UserWidget::where( 'user_id', $user->id )->count() > 0,
            'The user does not have widgets created for him'
        );
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateStoresWithSameName()
    {
        Sanctum::actingAs(
            Role::namespace('admin')->users->first(),
            ['*']
        );

        $company    =   $this->faker->company();

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', 'api/crud/ns.multistore', [
                'name'  =>  $company,
                'general'  => [
                    'status' => Store::STATUS_OPENED,
                    'roles_id' => Role::get()->map( fn( $role ) => $role->id )->toArray(),
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success',
        ]);

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', 'api/crud/ns.multistore', [
                'name'  =>  $company,
                'general'  => [
                    'status' => Store::STATUS_OPENED,
                    'roles_id' => Role::get()->map( fn( $role ) => $role->id )->toArray(),
                ]
            ]);

        $response->assertJson([
            'status'    =>  'error',
        ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testDeleteStore()
    {        
        Sanctum::actingAs(
            Role::namespace('admin')->users->first(),
            ['*']
        );

        $company    =   $this->faker->company();

        $data   =   [
            'name'  =>  $company,
            'general'  => [
                'status' => Store::STATUS_OPENED,
                'roles_id' => Role::get()->map( fn( $role ) => $role->id )->toArray(),
            ]
        ];

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', 'api/crud/ns.multistore', $data );

        $response->assertJson([
            'status'    =>  'success',
        ]);

        $data  =   json_decode( $response->getContent(), true );
        $store  =   $data[ 'data' ][ 'entry' ];

        ns()->store->setStore( Store::find( $store[ 'id' ] ) );

        /**
         * Will check if by switching the store
         * we're now pointing to the new store options
         */
        $optionTableName    =   'store_' . $store[ 'id' ] . '_' . 'nexopos_options';

        $this->assertTrue(
            ( new Option )->getTable() === $optionTableName,
            'The Option instance is not pointing to the store options'
        );

        $response = $this->withSession($this->app['session']->all())
            ->json('DELETE', 'api/crud/ns.multistore/' . $store[ 'id' ] );

        $response->assertJson([
            'status'    =>  'success',
        ]);

        /**
         * We'll now check if all
         * tables created for the store has been deleted
         */
        $tables = Schema::getTableListing();
        $storeTables = [];

        foreach ($tables as $tableName) {
            if (strpos($tableName, 'ns_store_' . $store['id'] . '_') === 0) {
                $storeTables[] = $tableName;
            }
        }

        $this->assertEmpty($storeTables);
    }
}
