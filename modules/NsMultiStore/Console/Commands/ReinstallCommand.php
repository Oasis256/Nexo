<?php
/**
 * NexoPOS MultiStore Command
 * @since  4.8.0
 * @package  modules/NsMultiStore
**/

namespace Modules\NsMultiStore\Console\Commands;

use Illuminate\Console\Command;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Services\StoresService;

class ReinstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:reinstall {id} {--slug}';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'Will reinstall an existing store.';

    public function handle()
    {
        /**
         * @var StoresService
         */
        $storesService  =   app()->make( StoresService::class );
        
        if ( ! $this->option( 'slug' ) ) {
            $store      =   Store::find( $this->argument( 'id' ) );
        } else {
            $store      =   Store::where( 'slug', $this->argument( 'id' ) )->first();
        }

        if ( $store instanceof Store ) {
            $this->info( sprintf( __m( 'Reinstalling the store "%s"...', 'NsMultiStore' ), $store->name ) );
            /**
             * Let's start by uninstalling all tables
             */
            $storesService->uninstallStore($store);

            /**
             * Now let's recreate the tables
             */
            $storesService->createStoreTables($store);

            /**
             * We'll dynamically change the status
             */
            $store->status = Store::STATUS_OPENED;
            $store->save();

            return $this->info( sprintf( __m( 'The store "%s" has been reinstalled!', 'NsMultiStore' ), $store->name ) );
        }

        return $this->error( sprintf( __m( 'Unable to locate the requested store "%s".', 'NsMultiStore' ), $this->argument( 'id' ) ) );
    }
}
