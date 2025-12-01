<?php
/**
 * Voucher Commission CRUD
 * @package GiftVouchers
 */

namespace Modules\GiftVouchers\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Services\CrudService;
use App\Services\CrudEntry;
use Modules\GiftVouchers\Models\VoucherCommission;

class VoucherCommissionCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'gift-vouchers.commissions';

    /**
     * Define the base table
     */
    protected $table = 'nexopos_gift_voucher_commissions';

    /**
     * Define the namespace
     */
    protected $namespace = 'gift-vouchers.commissions';

    /**
     * Model Used
     */
    protected $model = VoucherCommission::class;

    /**
     * Define the main route
     */
    protected $mainRoute = 'ns.gift-vouchers.commissions';

    /**
     * Define permissions
     */
    protected $permissions = [
        'read' => 'read.gift-voucher-commissions',
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
        ['nexopos_users as user', 'nexopos_gift_voucher_commissions.user_id', '=', 'user.id'],
        ['nexopos_products as product', 'nexopos_gift_voucher_commissions.product_id', '=', 'product.id'],
        ['nexopos_gift_vouchers as voucher', 'nexopos_gift_voucher_commissions.voucher_id', '=', 'voucher.id'],
        ['nexopos_orders as order', 'nexopos_gift_voucher_commissions.order_id', '=', 'order.id'],
    ];

    /**
     * Pick fields from relations
     */
    public $pick = [
        'user' => ['username'],
        'product' => ['name'],
        'voucher' => ['code'],
        'order' => ['code'],
    ];

    /**
     * Define casts
     */
    protected $casts = [
        'base_amount' => CurrencyCast::class,
        'value' => CurrencyCast::class,
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
            'list_title' => __('Voucher Commissions'),
            'list_description' => __('View gift voucher commission records'),
            'no_entry' => __('No commissions found'),
        ];
    }

    /**
     * Get links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/gift-vouchers/commissions'),
        ];
    }

    /**
     * Get table configuration
     */
    public function getTable(): array
    {
        return [
            'user_username' => [
                'label' => __('Earner'),
                '$direction' => '',
                '$sort' => false,
                'width' => '140px',
            ],
            'product_name' => [
                'label' => __('Product'),
                '$direction' => '',
                '$sort' => false,
            ],
            'voucher_code' => [
                'label' => __('Voucher'),
                '$direction' => '',
                '$sort' => false,
                'width' => '140px',
            ],
            'base_amount' => [
                'label' => __('Base Amount'),
                '$direction' => '',
                '$sort' => true,
                'width' => '120px',
            ],
            'commission_rate' => [
                'label' => __('Rate'),
                '$direction' => '',
                '$sort' => true,
                'width' => '80px',
            ],
            'commission_type' => [
                'label' => __('Type'),
                '$direction' => '',
                '$sort' => true,
                'width' => '100px',
            ],
            'value' => [
                'label' => __('Commission'),
                '$direction' => '',
                '$sort' => true,
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
     * Get form configuration - Commissions are read-only
     */
    public function getForm($entry = null): array
    {
        return [];
    }
}
