<?php

namespace Modules\Commission\Tests\Feature;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\CommissionCategory;
use Modules\Commission\Models\CommissionProductValue;
use Modules\Commission\Models\EarnedCommission;
use Modules\Commission\Services\CommissionCalculatorService;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CommissionCalculatorTest extends TestCase
{
    use WithAuthentication;

    protected CommissionCalculatorService $calculatorService;

    protected ?Commission $percentageCommission = null;

    protected ?Commission $fixedCommission = null;

    protected ?Commission $onTheHouseCommission = null;

    protected ?User $testUser = null;

    protected ?ProductCategory $testCategory = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->calculatorService = app(CommissionCalculatorService::class);
        $this->attemptAuthenticate();
        
        // Enable commissions in settings
        ns()->option->set('commission_enabled', 'yes');
    }

    /**
     * Create test fixtures
     */
    protected function createTestFixtures(): void
    {
        // Get or create test category
        $this->testCategory = ProductCategory::first();
        
        // Get or create test user with admin role
        $adminRole = Role::namespace('admin');
        $this->testUser = $adminRole->users->first();

        // Create percentage commission
        $this->percentageCommission = Commission::updateOrCreate(
            ['name' => 'Test Percentage Commission'],
            [
                'active' => true,
                'type' => Commission::TYPE_PERCENTAGE,
                'value' => 10,
                'role_id' => $adminRole->id,
                'calculation_base' => Commission::BASE_NET,
                'author' => $this->testUser->id,
            ]
        );

        // Create fixed commission
        $this->fixedCommission = Commission::updateOrCreate(
            ['name' => 'Test Fixed Commission'],
            [
                'active' => true,
                'type' => Commission::TYPE_FIXED,
                'value' => 5.00,
                'role_id' => $adminRole->id,
                'author' => $this->testUser->id,
            ]
        );

        // Create on_the_house commission
        $this->onTheHouseCommission = Commission::updateOrCreate(
            ['name' => 'Test On The House Commission'],
            [
                'active' => true,
                'type' => Commission::TYPE_ON_THE_HOUSE,
                'value' => 2.50,
                'role_id' => $adminRole->id,
                'author' => $this->testUser->id,
            ]
        );
    }

    /**
     * Clean up test fixtures
     */
    protected function cleanupTestFixtures(): void
    {
        Commission::where('name', 'like', 'Test %')->delete();
    }

    /**
     * Test percentage commission calculation
     */
    public function test_percentage_commission_calculation(): void
    {
        $this->createTestFixtures();

        try {
            // Create mock order product
            $orderProduct = new OrderProduct();
            $orderProduct->product_id = 1;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 100.00;
            $orderProduct->quantity = 2;
            $orderProduct->discount = 0;

            $value = $this->calculatorService->calculateCommissionValue(
                $this->percentageCommission,
                $orderProduct
            );

            // 10% of 100 * 2 = 20
            $this->assertEquals(20.00, $value);
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test fixed commission calculation
     */
    public function test_fixed_commission_calculation(): void
    {
        $this->createTestFixtures();

        try {
            $orderProduct = new OrderProduct();
            $orderProduct->product_id = 1;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 100.00;
            $orderProduct->quantity = 3;
            $orderProduct->discount = 0;

            $value = $this->calculatorService->calculateCommissionValue(
                $this->fixedCommission,
                $orderProduct
            );

            // 5.00 * 3 = 15.00
            $this->assertEquals(15.00, $value);
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test on_the_house commission calculation
     */
    public function test_on_the_house_commission_calculation(): void
    {
        $this->createTestFixtures();

        try {
            $orderProduct = new OrderProduct();
            $orderProduct->product_id = 1;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 50.00;
            $orderProduct->quantity = 4;
            $orderProduct->discount = 10.00; // Should be ignored

            $value = $this->calculatorService->calculateCommissionValue(
                $this->onTheHouseCommission,
                $orderProduct
            );

            // 2.50 * 4 = 10.00 (ignores price and discount)
            $this->assertEquals(10.00, $value);
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test fixed commission with product-specific value
     */
    public function test_fixed_commission_with_product_value(): void
    {
        $this->createTestFixtures();

        try {
            $product = Product::first();
            
            if (!$product) {
                $this->markTestSkipped('No products available for testing');
            }

            // Create product-specific value
            CommissionProductValue::updateOrCreate(
                [
                    'commission_id' => $this->fixedCommission->id,
                    'product_id' => $product->id,
                ],
                ['value' => 7.50]
            );

            $orderProduct = new OrderProduct();
            $orderProduct->product_id = $product->id;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 100.00;
            $orderProduct->quantity = 2;
            $orderProduct->discount = 0;

            $value = $this->calculatorService->calculateCommissionValue(
                $this->fixedCommission,
                $orderProduct
            );

            // Should use product-specific value: 7.50 * 2 = 15.00
            $this->assertEquals(15.00, $value);

            // Cleanup
            CommissionProductValue::where('commission_id', $this->fixedCommission->id)->delete();
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test commission preview
     */
    public function test_commission_preview(): void
    {
        $this->createTestFixtures();

        try {
            $preview = $this->calculatorService->previewCommission(
                productId: 1,
                productCategoryId: $this->testCategory->id,
                unitPrice: 100.00,
                quantity: 1,
                userId: $this->testUser->id
            );

            $this->assertArrayHasKey('value', $preview);
            $this->assertArrayHasKey('formatted_value', $preview);
            $this->assertArrayHasKey('commission', $preview);
            $this->assertIsFloat($preview['value']);
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test eligible users retrieval
     */
    public function test_get_eligible_commission_users(): void
    {
        $this->createTestFixtures();

        try {
            $users = $this->calculatorService->getEligibleCommissionUsers(
                $this->testCategory->id
            );

            $this->assertNotEmpty($users);
            $this->assertContains(
                $this->testUser->id,
                $users->pluck('id')->toArray()
            );
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test finding applicable commission
     */
    public function test_find_applicable_commission(): void
    {
        $this->createTestFixtures();

        try {
            $orderProduct = new OrderProduct();
            $orderProduct->product_id = 1;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 100.00;
            $orderProduct->quantity = 1;

            $commission = $this->calculatorService->findApplicableCommission(
                $this->testUser,
                $orderProduct
            );

            $this->assertNotNull($commission);
            $this->assertInstanceOf(Commission::class, $commission);
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test commission disabled returns no value
     */
    public function test_commission_disabled_returns_empty(): void
    {
        ns()->option->set('commission_enabled', 'no');

        try {
            // Create a mock order
            $order = Order::first();
            
            if (!$order) {
                $this->markTestSkipped('No orders available for testing');
            }

            $result = $this->calculatorService->processOrderCommissions($order);

            $this->assertEmpty($result);
        } finally {
            ns()->option->set('commission_enabled', 'yes');
        }
    }

    /**
     * Test percentage commission with gross base
     */
    public function test_percentage_commission_gross_base(): void
    {
        $this->createTestFixtures();

        try {
            // Update to use gross base
            $this->percentageCommission->update(['calculation_base' => Commission::BASE_GROSS]);

            $orderProduct = new OrderProduct();
            $orderProduct->product_id = 1;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 90.00;
            $orderProduct->quantity = 1;
            $orderProduct->discount = 10.00;

            $value = $this->calculatorService->calculateCommissionValue(
                $this->percentageCommission->fresh(),
                $orderProduct
            );

            // 10% of (90 + 10) = 10% of 100 = 10.00
            $this->assertEquals(10.00, $value);
        } finally {
            $this->cleanupTestFixtures();
        }
    }

    /**
     * Test percentage commission with net base
     */
    public function test_percentage_commission_net_base(): void
    {
        $this->createTestFixtures();

        try {
            $orderProduct = new OrderProduct();
            $orderProduct->product_id = 1;
            $orderProduct->product_category_id = $this->testCategory->id;
            $orderProduct->unit_price = 90.00;
            $orderProduct->quantity = 1;
            $orderProduct->discount = 10.00;

            $value = $this->calculatorService->calculateCommissionValue(
                $this->percentageCommission,
                $orderProduct
            );

            // 10% of 90 (net, after discount) = 9.00
            $this->assertEquals(9.00, $value);
        } finally {
            $this->cleanupTestFixtures();
        }
    }
}
