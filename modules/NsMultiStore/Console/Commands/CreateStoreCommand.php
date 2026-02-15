<?php
/**
 * NexoPOS MultiStore Command
 * @since  4.8.0
 * @package  modules/NsMultiStore
**/

namespace Modules\NsMultiStore\Console\Commands;

use App\Exceptions\NotAllowedException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Modules\NsMultiStore\Crud\StoreCrud;
use Modules\NsMultiStore\Events\MultiStoreAfterCreatedEvent;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;

class CreateStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:create {name} {--user=} {--roles=*} {--status=opened} {--description=}';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'Will create a multistore.';

    public function handle()
    {
        $this->info( 'Preparing...' );

        /**
         * We'll login the provider user
         * so all orders are assigned to him.
         */
        $user   =   User::where( 'email', $this->option( 'user' ) )->first();

        if ( ! $user instanceof User ) {
            return $this->error( __m( 'The requested user cannot be found.', 'NsMultiStore' ) );
        }

        Auth::login( $user );

        /**
         * @var StoresService
         */
        $storeService   =   app()->make( StoresService::class );

        /**
         * @var StoreCrud
         */
        $storeCrud  =   app()->make( StoreCrud::class );

        $roles      =   [];

        foreach( $this->option( 'roles' ) as $roleNamespace ) {
            $roles[]    =   Role::namespace( $roleNamespace )->id;
        }

        try {
            $response           =   ( object ) $storeCrud->submit([
                'name'          =>  $this->argument( 'name' ),
                'roles_id'      =>  $roles,
                'status'        =>  $this->option( 'status' ) ?: Store::STATUS_OPENED,
                'description'   =>  $this->option( 'description' ),
            ]);
        } catch( NotAllowedException $exception ) {
            return $this->error( $exception->getMessage() );
        }

        $this->info( 'Creating Tables...' );

        $storeService->createStoreTables( $response->data[ 'entry' ] );

        /**
         * We'll dynamically change the status
         */
        $response->data[ 'entry' ]->status = Store::STATUS_OPENED;
        $response->data[ 'entry' ]->save();

        MultiStoreAfterCreatedEvent::dispatch( $response->data[ 'entry' ] );

        $this->info( sprintf( __m( 'The store "%s" has been created!', 'NsMultiStore' ), $response->data[ 'entry' ]->name ) );
    }
}
