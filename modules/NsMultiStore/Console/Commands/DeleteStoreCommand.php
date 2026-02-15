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

class DeleteStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:delete {id} {--slug}';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'Deletes a store.';

    public function handle()
    {
        if ( ! $this->option( 'slug' ) ) {
            $store      =   Store::find( $this->argument( 'id' ) );
        } else {
            $store      =   Store::where( 'slug', $this->argument( 'id' ) )->first();
        }

        if ( ! $store instanceof Store ) {
            return $this->error( sprintf( __m( 'Unable to locate the requested store "%s".', 'NsMultiStore' ), $this->argument( 'id' ) ) );
        }

        /**
         * @var StoresService
         */
        $storeService   =   app()->make( StoresService::class );

        $storeService->dismantleStore( $store );

        $this->info( sprintf( __m( 'The store "%s" has been dismantled', 'NsMultiStore' ), $store->name ) );
    }
}
