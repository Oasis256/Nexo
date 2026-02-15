<?php
/**
 * NexoPOS MultiStore Command
 * @since  4.8.0
 * @package  modules/NsMultiStore
**/

namespace Modules\NsMultiStore\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Modules\NsMultiStore\Models\Store;

class ListStoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var  string
     */
    protected $signature = 'multistore:list';

    /**
     * The console command description.
     *
     * @var  string
     */
    protected $description = 'List all the available stores.';

    public function handle()
    {
        $stores  =   Store::get()->map( function( $store ) {
            return [
                'id'    =>  $store->id,
                'name'  =>  $store->name,
                'slug'  =>  $store->slug,
                'status'    =>  $store->status,
                'roles_id'  =>  collect( ( array ) json_decode( $store->roles_id ) )
                    ->map( fn( $id ) => Role::find( $id )?->name ?: __m( 'N/A', 'NsMultiStore' ) )
                    ->join( ', ' ),
                'created_at'    =>  $store->created_at
            ];
        })->toArray();
        
        $this->table([ 'id', 'name', 'slug', 'status', 'roles_id', 'created_at' ], $stores );
    }
}
