<?php
namespace Modules\NsMultiStore\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

class StoreRole extends Model
{
    protected $table = 'nexopos_stores_roles';

    public function role()
    {
        return $this->belongsTo( Role::class, 'role_id');
    }
}