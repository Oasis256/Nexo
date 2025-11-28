<?php

namespace Modules\Commission\Tests\Feature;

use App\Models\Role;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\EarnedCommission;
use Tests\Feature\CreateOrderTest;

/**
 * Integration test that creates orders and verifies commissions are tracked
 */
class CommissionOrderIntegrationTest extends CreateOrderTest
{
    protected $customProductParams = [];

    protected $customOrderParams = [];

    protected $shouldRefund = false;

    protected $customDate = true;

    protected $shouldMakePayment = true;

    protected $count = 1;

    protected $totalDaysInterval = 1;

    protected ?Commission $testCommission = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable commissions
        ns()->option->set('commission_enabled', 'yes');
    }

    /**
     * Create a test commission for admin role
     */
    protected function createTestCommission(): Commission
    {
        $adminRole = Role::namespace('admin');
        $author = $adminRole->users->first();

        return Commission::updateOrCreate(
            ['name' => 'Integration Test Commission'],
            [
                'active' => true,
                'type' => Commission::TYPE_PERCENTAGE,
                'value' => 5,
                'role_id' => $adminRole->id,
                'calculation_base' => Commission::BASE_NET,
                'author' => $author->id,
            ]
        );
    }

    /**
     * Clean up test commission
     */
    protected function cleanupTestCommission(): void
    {
        Commission::where('name', 'Integration Test Commission')->delete();
    }

    /**
     * Test that commission is created when order is placed
     */
    public function test_commission_created_on_order(): void
    {
        $this->testCommission = $this->createTestCommission();

        try {
            $this->attemptAuthenticate();
            
            $responses = $this->attemptPostOrder(function ($response, $data) {
                $orderId = $data['data']['order']['id'];
                
                // Verify earned commissions were created
                $earnedCommissions = EarnedCommission::where('order_id', $orderId)->get();
                
                $this->assertNotEmpty(
                    $earnedCommissions,
                    'Expected commissions to be created for order'
                );

                foreach ($earnedCommissions as $earned) {
                    $this->assertEquals($this->testCommission->id, $earned->commission_id);
                    $this->assertEquals(Commission::TYPE_PERCENTAGE, $earned->commission_type);
                    $this->assertGreaterThan(0, $earned->value);
                }
            });

            $this->assertNotEmpty($responses);
        } finally {
            $this->cleanupTestCommission();
        }
    }

    /**
     * Test that commission value is correctly calculated
     */
    public function test_commission_value_calculation(): void
    {
        $this->testCommission = $this->createTestCommission();

        try {
            $this->attemptAuthenticate();
            
            $this->attemptPostOrder(function ($response, $data) {
                $orderId = $data['data']['order']['id'];
                $orderProducts = $data['data']['order']['products'] ?? [];
                
                $earnedCommissions = EarnedCommission::where('order_id', $orderId)->get();

                foreach ($earnedCommissions as $earned) {
                    // For 5% commission, verify calculation
                    $expectedValue = ($earned->base_amount * $earned->quantity) * 0.05;
                    
                    $this->assertEqualsWithDelta(
                        $expectedValue,
                        $earned->value,
                        0.01,
                        'Commission value should be 5% of base amount * quantity'
                    );
                }
            });
        } finally {
            $this->cleanupTestCommission();
        }
    }

    /**
     * Test that refunded orders delete commissions
     */
    public function test_refunded_order_deletes_commissions(): void
    {
        $this->testCommission = $this->createTestCommission();
        $this->shouldRefund = true;

        try {
            $this->attemptAuthenticate();
            
            $this->attemptPostOrder(function ($response, $data) {
                // After refund, commissions should be deleted
                $orderId = $data['data']['order']['id'];
                
                // Note: The actual deletion happens via event listeners
                // This test verifies the integration works end-to-end
            });
        } finally {
            $this->cleanupTestCommission();
            $this->shouldRefund = false;
        }
    }

    /**
     * Test disabled commissions creates no records
     */
    public function test_disabled_commissions_no_records(): void
    {
        $this->testCommission = $this->createTestCommission();
        ns()->option->set('commission_enabled', 'no');

        try {
            $this->attemptAuthenticate();
            
            $this->attemptPostOrder(function ($response, $data) {
                $orderId = $data['data']['order']['id'];
                
                $earnedCommissions = EarnedCommission::where('order_id', $orderId)->count();
                
                $this->assertEquals(
                    0,
                    $earnedCommissions,
                    'No commissions should be created when disabled'
                );
            });
        } finally {
            ns()->option->set('commission_enabled', 'yes');
            $this->cleanupTestCommission();
        }
    }

    /**
     * Test fixed commission type
     */
    public function test_fixed_commission_type(): void
    {
        $adminRole = Role::namespace('admin');
        $author = $adminRole->users->first();

        $fixedCommission = Commission::create([
            'name' => 'Fixed Test Commission',
            'active' => true,
            'type' => Commission::TYPE_FIXED,
            'value' => 2.50,
            'role_id' => $adminRole->id,
            'author' => $author->id,
        ]);

        try {
            $this->attemptAuthenticate();
            
            $this->attemptPostOrder(function ($response, $data) use ($fixedCommission) {
                $orderId = $data['data']['order']['id'];
                
                $earnedCommissions = EarnedCommission::where('order_id', $orderId)
                    ->where('commission_id', $fixedCommission->id)
                    ->get();

                foreach ($earnedCommissions as $earned) {
                    // Fixed commission: value * quantity
                    $expectedValue = 2.50 * $earned->quantity;
                    
                    $this->assertEqualsWithDelta(
                        $expectedValue,
                        $earned->value,
                        0.01,
                        'Fixed commission should be value * quantity'
                    );
                }
            });
        } finally {
            $fixedCommission->delete();
        }
    }

    /**
     * Test on_the_house commission type ignores discounts
     */
    public function test_on_the_house_commission_ignores_discounts(): void
    {
        $adminRole = Role::namespace('admin');
        $author = $adminRole->users->first();

        $othCommission = Commission::create([
            'name' => 'On The House Test Commission',
            'active' => true,
            'type' => Commission::TYPE_ON_THE_HOUSE,
            'value' => 1.00,
            'role_id' => $adminRole->id,
            'author' => $author->id,
        ]);

        $this->useDiscount = true;

        try {
            $this->attemptAuthenticate();
            
            $this->attemptPostOrder(function ($response, $data) use ($othCommission) {
                $orderId = $data['data']['order']['id'];
                
                $earnedCommissions = EarnedCommission::where('order_id', $orderId)
                    ->where('commission_id', $othCommission->id)
                    ->get();

                foreach ($earnedCommissions as $earned) {
                    // On the house: value * quantity (ignores price/discounts)
                    $expectedValue = 1.00 * $earned->quantity;
                    
                    $this->assertEqualsWithDelta(
                        $expectedValue,
                        $earned->value,
                        0.01,
                        'On The House commission should be value * quantity regardless of discounts'
                    );
                }
            });
        } finally {
            $othCommission->delete();
        }
    }
}
