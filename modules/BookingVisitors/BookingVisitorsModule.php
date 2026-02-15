<?php
namespace Modules\BookingVisitors;

use Illuminate\Support\Facades\Event;
use App\Services\Module;

class BookingVisitorsModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );
    }
}