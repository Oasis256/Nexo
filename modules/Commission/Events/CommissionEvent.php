<?php

namespace Modules\Commission\Events;

use App\Models\Order;
use Modules\Commission\Crud\CommissionCrud;
use Modules\Commission\Crud\EarnedCommissionCrud;
use Modules\Commission\Services\CommissionCalculatorService;

/**
 * Main event handler for Commission module
 * Handles menu registration, CRUD registration, and order events
 */
class CommissionEvent
{
    /**
     * Register dashboard menus
     */
    public static function registerMenus(array $menus): array
    {
        if (ns()->allowedTo('commission.read')) {
            $menus['commissions'] = [
                'label' => __m('Commissions', 'Commission'),
                'icon' => 'la-percentage',
                'permissions' => ['commission.read', 'commission.dashboard'],
                'childrens' => [
                    'dashboard' => [
                        'label' => __m('Dashboard', 'Commission'),
                        'permissions' => ['commission.dashboard'],
                        'href' => ns()->route('commission.dashboard'),
                    ],
                    'commissions' => [
                        'label' => __m('Commission Rates', 'Commission'),
                        'permissions' => ['commission.read'],
                        'href' => ns()->route('commission.list'),
                    ],
                    'earned' => [
                        'label' => __m('Earned Commissions', 'Commission'),
                        'permissions' => ['commission.earnings.read'],
                        'href' => ns()->route('commission.earned.list'),
                    ],
                    'reports' => [
                        'label' => __m('Reports', 'Commission'),
                        'permissions' => ['commission.reports'],
                        'href' => ns()->route('commission.reports'),
                    ],
                    'settings' => [
                        'label' => __m('Settings', 'Commission'),
                        'permissions' => ['commission.settings'],
                        'href' => ns()->route('ns.dashboard.settings', ['settings' => 'commission.settings']),
                    ],
                ],
            ];
        }

        return $menus;
    }

    /**
     * Register CRUD resources
     */
    public static function registerCrud(string $namespace, ?string $identifier): string
    {
        // If identifier is null, return namespace unchanged
        if ($identifier === null) {
            return $namespace;
        }

        return match ($identifier) {
            CommissionCrud::IDENTIFIER => CommissionCrud::class,
            EarnedCommissionCrud::IDENTIFIER => EarnedCommissionCrud::class,
            default => $namespace,
        };
    }

    /**
     * Track commissions when order is created/updated
     */
    public static function trackCommissions($event): void
    {
        $order = $event->order ?? $event;

        // Only process paid orders
        if (!$order instanceof Order || $order->payment_status !== Order::PAYMENT_PAID) {
            return;
        }

        // Check if commissions are enabled
        if (ns()->option->get('commission_enabled', 'yes') !== 'yes') {
            return;
        }

        $calculatorService = app(CommissionCalculatorService::class);
        $calculatorService->processOrderCommissions($order);
    }

    /**
     * Delete commissions when order is deleted/refunded
     */
    public static function deleteCommissions($event): void
    {
        $order = $event->order ?? $event;

        if (!$order instanceof Order) {
            return;
        }

        $calculatorService = app(CommissionCalculatorService::class);
        $deletedCount = $calculatorService->deleteOrderCommissions($order);

        if ($deletedCount > 0) {
            CommissionAfterDeletedEvent::dispatch($order->id, $deletedCount);
        }
    }
}