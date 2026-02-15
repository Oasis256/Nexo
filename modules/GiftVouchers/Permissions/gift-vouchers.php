<?php
/**
 * Gift Vouchers Permissions
 * @package GiftVouchers
 */

use App\Models\Permission;
use App\Models\Role;

if (defined('NEXO_CREATE_PERMISSIONS')) {
    // CRUD permissions for voucher templates
    foreach (['create', 'read', 'update', 'delete'] as $crud) {
        $permission = Permission::firstOrNew(['namespace' => $crud . '.gift-voucher-templates']);
        $permission->name = ucwords($crud) . ' Gift Voucher Templates';
        $permission->namespace = $crud . '.gift-voucher-templates';
        $permission->description = sprintf(__('Can %s gift voucher templates'), $crud);
        $permission->save();
    }

    // CRUD permissions for vouchers
    foreach (['create', 'read', 'update', 'delete'] as $crud) {
        $permission = Permission::firstOrNew(['namespace' => $crud . '.gift-vouchers']);
        $permission->name = ucwords($crud) . ' Gift Vouchers';
        $permission->namespace = $crud . '.gift-vouchers';
        $permission->description = sprintf(__('Can %s gift vouchers'), $crud);
        $permission->save();
    }

    // Special permissions for voucher operations
    $specialPermissions = [
        [
            'namespace' => 'redeem.gift-vouchers',
            'name' => 'Redeem Gift Vouchers',
            'description' => __('Can redeem gift vouchers at POS'),
        ],
        [
            'namespace' => 'cancel.gift-vouchers',
            'name' => 'Cancel Gift Vouchers',
            'description' => __('Can cancel gift vouchers and reverse accounting'),
        ],
        [
            'namespace' => 'read.gift-voucher-redemptions',
            'name' => 'View Gift Voucher Redemptions',
            'description' => __('Can view gift voucher redemption history'),
        ],
        [
            'namespace' => 'read.gift-voucher-commissions',
            'name' => 'View Gift Voucher Commissions',
            'description' => __('Can view gift voucher commissions'),
        ],
        [
            'namespace' => 'manage.gift-voucher-commissions',
            'name' => 'Manage Gift Voucher Commissions',
            'description' => __('Can manage and adjust gift voucher commissions'),
        ],
        [
            'namespace' => 'regenerate.gift-voucher-qr',
            'name' => 'Regenerate Gift Voucher QR',
            'description' => __('Can regenerate QR codes for gift vouchers'),
        ],
    ];

    foreach ($specialPermissions as $permData) {
        $permission = Permission::firstOrNew(['namespace' => $permData['namespace']]);
        $permission->name = $permData['name'];
        $permission->namespace = $permData['namespace'];
        $permission->description = $permData['description'];
        $permission->save();
    }

    // Assign all gift voucher permissions to admin role
    $admin = Role::namespace(Role::ADMIN);
    if ($admin) {
        $giftVoucherPermissions = Permission::where('namespace', 'like', '%gift-voucher%')->get();
        $admin->addPermissions($giftVoucherPermissions->pluck('namespace'));
    }

    // Assign redemption permissions to store cashier
    $cashier = Role::namespace(Role::STORECASHIER);
    if ($cashier) {
        $cashier->addPermissions([
            'read.gift-vouchers',
            'redeem.gift-vouchers',
        ]);
    }

    // Assign basic permissions to store admin
    $storeAdmin = Role::namespace(Role::STOREADMIN);
    if ($storeAdmin) {
        $storeAdmin->addPermissions([
            'create.gift-voucher-templates',
            'read.gift-voucher-templates',
            'update.gift-voucher-templates',
            'create.gift-vouchers',
            'read.gift-vouchers',
            'update.gift-vouchers',
            'redeem.gift-vouchers',
            'cancel.gift-vouchers',
            'read.gift-voucher-redemptions',
            'read.gift-voucher-commissions',
            'regenerate.gift-voucher-qr',
        ]);
    }
}
