<?php

namespace Modules\NsCommissions\Events;

use App\Models\Order;
use Modules\NsCommissions\Crud\CommissionsCrud;
use Modules\NsCommissions\Crud\EarnedCommissionCrud;
use Modules\NsCommissions\Models\EarnedCommission;
use Modules\NsCommissions\Services\CommissionCalculatorService;
use Modules\NsMultiStore\Services\StoresService;

/**
 * Register Events
 **/
class NsCommissionsEvent
{
    public function __construct()
    {
        //
    }

    /**
     * Will delete all commission for
     * an order that has been refunded
     */
    public static function deleteCommissions($event)
    {
        $service = app(CommissionCalculatorService::class);
        $service->deleteOrderCommissions($event->order);
    }

    /**
     * Track and calculate commissions for a completed order
     * Uses CommissionCalculatorService for per-product processing
     * Handles both OrderAfterCreatedEvent and OrderAfterUpdatedEvent
     */
    public static function trackCommissions($event)
    {
        $order = $event->order;
        
        // Only process for paid orders
        if ($order->payment_status === Order::PAYMENT_PAID) {
            $service = app(CommissionCalculatorService::class);
            
            // Check if commissions already exist for this order
            $existingCommissions = EarnedCommission::where('order_id', $order->id)->count();
            
            // Only create commissions if none exist (avoid duplicates on updates)
            if ($existingCommissions === 0) {
                $service->processOrderCommissions($order);
            }
        }
    }

    /**
     * will customize the actual menus
     *
     * @param  array  $menus
     * @return array
     */
    public static function registerMenus($menus)
    {
        if (isset($menus['users']) && (ns()->store instanceof StoresService && ns()->store->isMultiStore())) {
            $menus['users']['childrens']['ns-commissions'] = [
                'label' =>  __m('Commissions', 'NsCommissions'),
                'permissions'   =>  [
                    'ns.commissions-create',
                    'ns.commissions-delete',
                    'ns.commissions-update',
                    'ns.commissions-read',
                ],
                'href'  =>  ns()->route('commissions.list'),
            ];
        }

        if ( isset( $menus[ 'ns.multistore-users' ] ) && (ns()->store instanceof StoresService && ns()->store->isMultiStore())) {
            $menus['ns.multistore-users']['childrens']['ns-commissions'] = [
                'label' =>  __m('Commissions', 'NsCommissions'),
                'permissions'   =>  [
                    'ns.commissions-create',
                    'ns.commissions-delete',
                    'ns.commissions-update',
                    'ns.commissions-read',
                ],
                'href'  =>  ns()->route('commissions.list'),
            ];
        }

        if (isset($menus['users']) && ! ns()->store instanceof StoresService) {
            $menus['commissions'] = [
                'label' =>  __m('Commissions', 'NsCommissions'),
                'permissions'   =>  [
                    'ns.commissions-create',
                    'ns.commissions-delete',
                    'ns.commissions-update',
                    'ns.commissions-read',
                ],
                'icon'  =>  'la-coins',
                'href'  =>  ns()->route('commissions.list'),
            ];
        }

        if (isset($menus['orders'])) {
            $menus['orders']['childrens']['ns-commissions-earned'] = [
                'label' =>  __m('Earned Commissions', 'NsCommissions'),
                'permissions'   =>  [
                    'ns.commissions-create',
                    'ns.commissions-delete',
                    'ns.commissions-update',
                    'ns.commissions-read',
                ],
                'href'  =>  ns()->route('earned-commissions.list'),
            ];
        }

        if (isset($menus['reports'])) {
            $menus['reports']['childrens']['ns-commissions-reports'] = [
                'label' =>  __m('Commissions', 'NsCommissions'),
                'permissions'   =>  [
                    'ns.commissions-reports',
                ],
                'href'  =>  ns()->route('commissions.reports-commissions'),
            ];
        }

        if (isset($menus['settings'])) {
            $menus['settings']['childrens']['ns-commissions-settings'] = [
                'label' =>  __m('Commissions Settings', 'NsCommissions'),
                'permissions'   =>  [
                    'manage.options',
                ],
                'href'  =>  ns()->route('ns.dashboard.settings', [
                    'settings'  =>  'ns.commissions-settings',
                ]),
            ];
        }

        return $menus;
    }

    /**
     * this will register the crud component
     * for the commissions.
     */
    public static function registerCrud($identifier)
    {
        switch ($identifier) {
            case 'ns.commissions': return CommissionsCrud::class;
            case 'ns.earned-commissions': return EarnedCommissionCrud::class;
            default: return $identifier;
        }
    }
}
