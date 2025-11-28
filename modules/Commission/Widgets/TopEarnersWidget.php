<?php

namespace Modules\Commission\Widgets;

use App\Services\WidgetService;

class TopEarnersWidget extends WidgetService
{
    protected $vueComponent = 'nsCommissionTopEarners';

    public function __construct()
    {
        $this->name = __( 'Top Commission Earners' );
        $this->description = __( 'Display the top commission earners for the current period.' );
        $this->permission = 'commission.dashboard';
    }
}
