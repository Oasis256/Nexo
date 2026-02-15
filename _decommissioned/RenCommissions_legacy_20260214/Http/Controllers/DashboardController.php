<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    protected function getRoutes(): array
    {
        return [
            'web' => [
                'dashboard' => ns()->route('rencommissions.dashboard'),
                'commissions' => ns()->route('rencommissions.commissions'),
                'types' => ns()->route('rencommissions.types'),
                'staff_earnings' => ns()->route('rencommissions.staff-earnings'),
                'pending_payouts' => ns()->route('rencommissions.pending-payouts'),
                'payment_history' => ns()->route('rencommissions.payment-history'),
                'my_commissions' => ns()->route('rencommissions.my-commissions'),
            ],
            'api' => [
                'dashboard_summary' => ns()->route('rencommissions.api.dashboard.summary'),
                'dashboard_recent' => ns()->route('rencommissions.api.dashboard.recent'),
                'dashboard_commissions' => ns()->route('rencommissions.api.dashboard.commissions'),
                'dashboard_leaderboard' => ns()->route('rencommissions.api.dashboard.leaderboard'),
                'dashboard_trends' => ns()->route('rencommissions.api.dashboard.trends'),
                'dashboard_staff_earnings' => ns()->route('rencommissions.api.dashboard.staff-earnings'),
                'dashboard_types' => ns()->route('rencommissions.api.dashboard.types'),
                'commission_mark_paid' => ns()->route('rencommissions.api.commission.mark-paid', [ 'id' => '__ID__' ]),
                'commission_void' => ns()->route('rencommissions.api.commission.void', [ 'id' => '__ID__' ]),
                'commission_bulk_action' => ns()->route('rencommissions.api.commission.bulk'),
                'commission_export' => ns()->route('rencommissions.api.commission.export'),
                'dashboard_type_update' => ns()->route('rencommissions.api.dashboard.types.update', [ 'id' => '__ID__' ]),
                'dashboard_type_delete' => ns()->route('rencommissions.api.dashboard.types.delete', [ 'id' => '__ID__' ]),
                'my_commissions' => ns()->route('rencommissions.api.my-commissions'),
                'my_summary' => ns()->route('rencommissions.api.my-summary'),
            ],
        ];
    }

    protected function renderPage(string $view, string $title)
    {
        return view($view, [
            'title' => __($title),
            'renCommissionsRoutes' => $this->getRoutes(),
        ]);
    }

    public function index()
    {
        return $this->renderPage('RenCommissions::dashboard.index', 'Commissions Dashboard');
    }

    public function staffEarnings()
    {
        return $this->renderPage('RenCommissions::dashboard.staff-earnings', 'Staff Earnings');
    }

    public function commissions()
    {
        return $this->renderPage('RenCommissions::dashboard.commissions', 'All Commissions');
    }

    public function types()
    {
        return $this->renderPage('RenCommissions::dashboard.types', 'Commission Types');
    }

    public function pendingPayouts()
    {
        return $this->renderPage('RenCommissions::dashboard.pending-payouts', 'Pending Payouts');
    }

    public function paymentHistory()
    {
        return $this->renderPage('RenCommissions::dashboard.payment-history', 'Payment History');
    }

    public function myCommissions()
    {
        return $this->renderPage('RenCommissions::dashboard.my-commissions', 'My Commissions');
    }
}
