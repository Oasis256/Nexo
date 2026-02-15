<?php
/**
 * NexoPOS MultiStore Command
 * @since  4.8.2
 * @package  modules/NsMultiStore
**/

namespace Modules\NsMultiStore\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeEverythingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:wipe {--force}';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'Describe what does the command.';

    public function handle()
    {
        if ( ! $this->option( 'force' ) ) {
            $ask    =   $this->ask( 'Would you like to clear every stores and tables and clear everything? Will be deleted forever! [Y/N]' );
        } else {
            $ask    =   'y';
        }
        
        if ( strtolower( $ask ) === 'y' ) {
            $tables     =   DB::select( 'SHOW TABLES' );
            $dbPrefix   =   env( 'DB_PREFIX' );
            $database   =   env( 'DB_DATABASE' );

            $validTables    =   collect( $tables )->map( function( $table ) use ( $dbPrefix ) {
                $tableName  =   array_values( ( array ) $table )[0];
                return preg_match( "/store_[0-9]{1,}_/", $tableName ) ? substr( $tableName, strlen( $dbPrefix ) ) : false;
            })->filter();

            if( count( $validTables ) > 0 ) {
                $this->withProgressBar( $validTables, function( $tableName ) {
                    Schema::drop( $tableName );
                });
    
                $this->newLine();
    
                $this->info( sprintf( "%s tables were dropped !", $validTables->count() ) );
            } else {
                $this->info( "There is no valid table to drop." );
            }

            DB::table('nexopos_stores')->truncate();
            DB::table('nexopos_stores_migrations')->truncate();

            $this->newLine();

            $this->info( 'The multistore installation was successfully deleted.' );
        }
    }
}
