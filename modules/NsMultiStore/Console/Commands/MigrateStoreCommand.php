<?php
/**
 * NexoPOS MultiStore Command
 * @since  4.8.0
 * @package  modules/NsMultiStore
**/

namespace Modules\NsMultiStore\Console\Commands;

use Illuminate\Console\Command;
use Modules\NsMultiStore\Models\Store;
use Modules\NsMultiStore\Models\StoreMigration;
use Modules\NsMultiStore\Services\StoresService;

class MigrateStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:migrate {id} {--slug}';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'Describe what does the command.';

    public function handle()
    {
        /**
         * @var StoresService
         */
        $storeService   =   app()->make( StoresService::class );

        if ( ! $this->option( 'slug' ) ) {
            $store      =   Store::find( $this->argument( 'id' ) );
        } else {
            $store      =   Store::where( 'slug', $this->argument( 'id' ) )->first();
        }

        if ( $store instanceof Store ) {
            $storeService->setStore( $store );

            $migrations     =   $storeService->getMigrations( $store );

            /**
             * if there is any migration
             * that needs to be executed
             */
            if ( count( $migrations ) > 0 ) {
                $this->withProgressBar( $migrations, function( $file ) use ( $storeService, $store ) {
                    $storeService->triggerFile(
                        $store,
                        $file,
                        'up'
                    );
                });

                $this->newLine();
                return $this->info( sprintf( __m( 'The migration executed successfully for "%s"' ), $store->name ) );

            } else {
                return $this->info( sprintf( __m( 'There is no migration to execute for "%s"' ), $store->name ) );
            }
        }

        return $this->error( sprintf( __m( 'Unable to locate the requested store "%s".', 'NsMultiStore' ), $this->argument( 'id' ) ) );
    }
}
