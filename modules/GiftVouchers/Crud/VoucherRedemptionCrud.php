<?php
/**
 * Voucher Redemption CRUD
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Services\CrudService;
use App\Services\CrudEntry;
use Modules\GiftVouchers\Models\VoucherRedemption;

class VoucherRedemptionCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'gift-vouchers.redemptions';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_gift_voucher_redemptions';

    /**
     * Define the namespace
     */
    protected $namespace = 'gift-vouchers.redemptions';

    /**
     * Model Used
     */
    protected $model = VoucherRedemption::class;

    /**
     * Define the main route
     */
    protected $mainRoute = 'ns.gift-vouchers.redemptions';

    /**
     * Define permissions
     */
    protected $permissions = [
        'read' => 'read.gift-voucher-redemptions',
    ];

    /**
     * Define features
     */
    protected $features = [
        'bulk-actions' => false,
        'single-action' => true,
        'checkboxes' => false,
    ];

    /**
     * Adding relations
     */
    public $relations = [
        ['nexopos_users as author', 'nexopos_gift_voucher_redemptions.author', '=', 'author.id'],
        ['nexopos_gift_vouchers as voucher', 'nexopos_gift_voucher_redemptions.voucher_id', '=', 'voucher.id'],
        ['nexopos_users as redeemer', 'nexopos_gift_voucher_redemptions.redeemer_id', '=', 'redeemer.id'],
        ['nexopos_orders as order', 'nexopos_gift_voucher_redemptions.redemption_order_id', '=', 'order.id'],
    ];

    /**
     * Pick fields from relations
     */
    public $pick = [
        'author' => ['username'],
        'voucher' => ['code'],
        'redeemer' => ['first_name', 'last_name'],
        'order' => ['code'],
    ];

    /**
     * Define casts
     */
    protected $casts = [
        'total_value' => CurrencyCast::class,
        'created_at' => DateCast::class,
    ];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the label used for the CRUD
     */
    public function getLabels(): array
    {
        return [
            'list_title' => __('Voucher Redemptions'),
            'list_description' => __('View gift voucher redemption history'),
            'no_entry' => __('No redemptions found'),
        ];
    }

    /**
     * Get links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/gift-vouchers/redemptions'),
        ];
    }

    /**
     * Get table configuration
     */
    public function getTable(): array
    {
        return [
            'voucher_code' => [
                'label' => __('Voucher'),
                '$direction' => '',
                '$sort' => false,
                'width' => '140px',
            ],
            'redeemer_first_name' => [
                'label' => __('Redeemer'),
                '$direction' => '',
                '$sort' => false,
            ],
            'total_value' => [
                'label' => __('Value Redeemed'),
                '$direction' => '',
                '$sort' => true,
                'width' => '140px',
            ],
            'order_code' => [
                'label' => __('Order'),
                '$direction' => '',
                '$sort' => false,
                'width' => '140px',
            ],
            'author_username' => [
                'label' => __('Processed By'),
                '$direction' => '',
                '$sort' => false,
                'width' => '120px',
            ],
            'created_at' => [
                'label' => __('Date'),
                '$direction' => 'desc',
                '$sort' => true,
                'width' => '150px',
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions(CrudEntry $entry): CrudEntry
    {
        $entry->action(
            label: __('View Voucher'),
            namespace: 'view_voucher',
            type: 'GOTO',
            url: ns()->url('dashboard/gift-vouchers/view/' . $entry->voucher_id),
        );

        return $entry;
    }

    /**
     * Get form configuration - Redemptions are read-only
     */
    public function getForm($entry = null): array
    {
        return [];
    }
}
