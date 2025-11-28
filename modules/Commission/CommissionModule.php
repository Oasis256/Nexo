<?php
namespace Modules\Commission;

use Illuminate\Support\Facades\Event;
use App\Services\Module;

class CommissionModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );
    }
}