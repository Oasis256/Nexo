<?php

namespace Modules\Commission\Widgets;

use App\Services\WidgetService;

class RecentCommissionsWidget extends WidgetService
{
    protected $vueComponent = 'nsCommissionRecent';

    public function __construct()
    {
        $this->name = __( 'Recent Commissions' );
        $this->description = __( 'Display the most recent commission transactions.' );
        $this->permission = 'commission.dashboard';
    }
}
