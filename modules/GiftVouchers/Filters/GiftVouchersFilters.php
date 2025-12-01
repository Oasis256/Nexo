<?php

namespace Modules\GiftVouchers\Filters;

use Modules\GiftVouchers\Crud\VoucherCommissionCrud;
use Modules\GiftVouchers\Crud\VoucherCrud;
use Modules\GiftVouchers\Crud\VoucherRedemptionCrud;
use Modules\GiftVouchers\Crud\VoucherTemplateCrud;

class GiftVouchersFilters
{
    /**
     * Register dashboard menu items for GiftVouchers module
     */
    public static function dashboardMenus(array $menus): array
    {
        // Add main Gift Vouchers menu
        $menus['gift-vouchers'] = [
            'label' => __m('Gift Vouchers', 'GiftVouchers'),
            'icon' => 'la-gift',
            'permissions' => [
                'read.gift-voucher-templates',
                'read.gift-vouchers',
            ],
            'childrens' => [
                'voucher-templates' => [
                    'label' => __m('Templates', 'GiftVouchers'),
                    'permissions' => ['read.gift-voucher-templates'],
                    'href' => ns()->route('ns.gift-vouchers.templates'),
                ],
                'vouchers' => [
                    'label' => __m('Vouchers', 'GiftVouchers'),
                    'permissions' => ['read.gift-vouchers'],
                    'href' => ns()->route('ns.gift-vouchers.vouchers'),
                ],
                'redemptions' => [
                    'label' => __m('Redemptions', 'GiftVouchers'),
                    'permissions' => ['read.gift-voucher-redemptions'],
                    'href' => ns()->route('ns.gift-vouchers.redemptions'),
                ],
                'commissions' => [
                    'label' => __m('Commissions', 'GiftVouchers'),
                    'permissions' => ['read.gift-voucher-commissions'],
                    'href' => ns()->route('ns.gift-vouchers.commissions'),
                ],
            ],
        ];

        // Add settings menu item under existing settings menu
        if (isset($menus['settings'])) {
            $menus['settings']['childrens']['gift-vouchers-settings'] = [
                'label' => __m('Gift Vouchers', 'GiftVouchers'),
                'permissions' => ['manage.options'],
                'href' => ns()->route('ns.dashboard.settings', [
                    'settings' => 'gift-vouchers.settings',
                ]),
            ];
        }

        return $menus;
    }

    /**
     * Register CRUD resources for the module
     */
    public static function registerCrud(string $identifier): string
    {
        return match ($identifier) {
            'gift-vouchers.templates' => VoucherTemplateCrud::class,
            'gift-vouchers.vouchers' => VoucherCrud::class,
            'gift-vouchers.redemptions' => VoucherRedemptionCrud::class,
            'gift-vouchers.commissions' => VoucherCommissionCrud::class,
            default => $identifier,
        };
    }
}
