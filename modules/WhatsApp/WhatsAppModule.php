<?php
namespace Modules\WhatsApp;

use Illuminate\Support\Facades\Event;
use App\Services\Module;

class WhatsAppModule extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );
    }
}