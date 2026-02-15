<?php

namespace Modules\Commission\Widgets;

use App\Services\WidgetService;

class TotalEarningsWidget extends WidgetService
{
    protected $vueComponent = 'nsCommissionTotalEarnings';

    public function __construct()
    {
        $this->name = __( 'Total Commission Earnings' );
        $this->description = __( 'Display total commission earnings for the current period.' );
        $this->permission = 'commission.dashboard';
    }
}
