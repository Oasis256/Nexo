<?php

namespace Modules\NsCommissions\Tests\Feature;

use App\Models\Role;
use Exception;
use Modules\NsCommissions\Models\Commission;
use Modules\NsCommissions\Models\EarnedCommission;
use Tests\Feature\CreateOrderTest;

class CreateCommissionTest extends CreateOrderTest
{
    protected $customProductParams = [];

    protected $customOrderParams = [];

    protected $shouldRefund = true;

    protected $customDate = true;

    protected $shouldMakePayment = true;

    protected $count = 1;

    protected $totalDaysInterval = 1;

    public function testCommissionCreation()
    {
        $name = __m('Test Commissions 5% Admin', 'NsCommissions');
        $commission = Commission::where('name', $name)->first();

        if ($commission instanceof Commission) {
            $commission = new Commission;
        }

        $commission->name = $name;
        $commission->active = true;
        $commission->type = 'percentage';
        $commission->value = 5;
        $commission->role_id = Role::namespace('admin')->id;
        $commission->author = Role::namespace('admin')->users->first()->id;
        $commission->save();

        $this->testPostingOrder(function ($response, $data) {
            $earnedCommissions = EarnedCommission::where('order_id', $data['data']['order']['id'])->first();

            if (! $earnedCommissions instanceof EarnedCommission) {
                throw new Exception(__m('Unable to generate a commissions for the sale.', 'NsCommissions'));
            }
        });
    }
}
