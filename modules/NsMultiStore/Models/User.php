<?php

namespace Modules\NsMultiStore\Models;

use App\Models\User as ModelsUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class User extends ModelsUser
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope('store_users', function (Builder $builder) {
            if (ns()->store->isMultiStore() && ns()->store->subDomainsEnabled()) {
                $roles_id = json_decode(Store::current()->roles_id);
                
                $builder->whereExists( function( $query ) use ( $roles_id ) {
                    $query->select( DB::raw(1) )
                        ->from( 'nexopos_users_roles_relations' )
                        ->whereColumn( 'nexopos_users_roles_relations.user_id', 'nexopos_users.id' )
                        ->whereIn( 'nexopos_users_roles_relations.role_id', $roles_id );
                });
            }
        });
    }
}
