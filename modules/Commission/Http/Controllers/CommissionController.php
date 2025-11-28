<?php

namespace Modules\Commission\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use Modules\Commission\Crud\CommissionCrud;
use Modules\Commission\Crud\EarnedCommissionCrud;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\EarnedCommission;

/**
 * Handles Commission CRUD views
 */
class CommissionController extends BaseDashboardController
{
    /**
     * List all commissions
     */
    public function list()
    {
        ns()->restrict(['commission.read']);

        return CommissionCrud::table();
    }

    /**
     * Create new commission form
     */
    public function create()
    {
        ns()->restrict(['commission.create']);

        return CommissionCrud::form();
    }

    /**
     * Edit commission form
     */
    public function edit(Commission $commission)
    {
        ns()->restrict(['commission.update']);

        return CommissionCrud::form($commission);
    }

    /**
     * List earned commissions
     */
    public function earnedList()
    {
        ns()->restrict(['commission.earnings.read']);

        return EarnedCommissionCrud::table();
    }

    /**
     * View earned commission details
     */
    public function earnedView(EarnedCommission $earnedCommission)
    {
        ns()->restrict(['commission.earnings.read']);

        return view('Commission::earned.view', [
            'title' => __m('Earned Commission Details', 'Commission'),
            'description' => __m('View details of this earned commission.', 'Commission'),
            'earnedCommission' => $earnedCommission->load([
                'user',
                'order',
                'orderProduct',
                'product',
                'commission',
            ]),
        ]);
    }
}
