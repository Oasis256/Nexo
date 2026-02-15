<?php
/**
 * NexoPOS MultiStore Command
 * @since  4.8.0
 * @package  modules/NsMultiStore
**/

namespace Modules\NsMultiStore\Console\Commands;

use App\Models\Role;
use App\Services\DemoService;
use App\Services\ResetService;
use Database\Seeders\DefaultSeeder;
use Database\Seeders\FirstDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Modules\NsMultiStore\Models\Store;

class ResetStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:reset {id} {--slug} {--with-sales} {--with-procurement} {--option=default}';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'Will wipe a store and optionally enable a demo.';

    public function handle()
    {
        /**
         * @var DemoService
         */
        $demoService    =   app()->make( DemoService::class );

        /**
         * @var ResetService
         */
        $resetService    =   app()->make( ResetService::class );

        if ( ! $this->option( 'slug' ) ) {
            $store      =   Store::find( $this->argument( 'id' ) );
        } else {
            $store      =   Store::where( 'slug', $this->argument( 'id' ) )->first();
        }

        if ( $store instanceof Store ) {
            ns()->store->setStore( $store );

            $resetService->softReset();

            $role   =   Role::namespace( Role::ADMIN );
            
            if ( $role->users->count() === 0 ) {
                return $this->error( __m( 'An administrator is required to perform this operation.', 'NsMultiStore' ) );
            }
            
            /**
             * The demo needs someone
             * to be connected for proceeding.
             */
            Auth::login( $role->users->first() );

            $config     =   [
                'create_sales'  =>  $this->option( 'with-sales' ),
                'create_procurements'   =>  $this->option( 'with-procurement' )
            ];

            switch ( $this->option( 'option' ) ) {
                case 'wipe_plus_grocery':
                    $demoService->run( $config );
                break;
                case 'wipe_plus_simple':
                    ( new FirstDemoSeeder )->run();
                break;
                case 'default':
                    ( new DefaultSeeder )->run();
                break;
                default:
                    $this->resetService->handleCustom( $config );
                break;
            }

            return $this->info( sprintf( __m( 'The demo has been enabled for "%s"', 'NsMultiStore' ), $store->name ) );
        }

        return $this->error( sprintf( __m( 'Unable to locate the requested store "%s".', 'NsMultiStore' ), $this->argument( 'id' ) ) );
    }
}
