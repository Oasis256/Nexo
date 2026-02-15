<?php

namespace Modules\Commission\Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Role;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\CommissionCategory;
use Modules\Commission\Models\CommissionCategoryUser;
use Modules\Commission\Models\CommissionProductValue;
use Modules\Commission\Models\EarnedCommission;
use Modules\Commission\Services\CommissionCalculatorService;
use Modules\Commission\Services\CommissionService;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CommissionModuleTest extends TestCase
{
    use WithAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attemptAuthenticate();
    }

    /**
     * Test complete commission module workflow
     * Create category -> Add users -> Create commission -> Process order
     */
    public function test_complete_commission_workflow(): void
    {
        // Step 1: Create a commission category
        $category = CommissionCategory::create([
            'name' => 'Sales Team ' . time(),
            'description' => 'Sales team commission category',
            'author' => $this->users[0]->id,
        ]);

        $this->assertDatabaseHas('nexopos_commission_categories', [
            'id' => $category->id,
            'name' => $category->name,
        ]);

        // Step 2: Add users to category
        $user = User::first();
        CommissionCategoryUser::create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'author' => $this->users[0]->id,
        ]);

        $this->assertDatabaseHas('nexopos_commission_category_users', [
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        // Step 3: Create a percentage commission
        $commission = Commission::create([
            'name' => 'Percentage Commission ' . time(),
            'type' => Commission::TYPE_PERCENTAGE,
            'base' => Commission::BASE_GROSS,
            'value' => 10, // 10%
            'active' => true,
            'category_id' => $category->id,
            'author' => $this->users[0]->id,
        ]);

        $this->assertDatabaseHas('nexopos_commissions', [
            'id' => $commission->id,
            'type' => Commission::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        // Step 4: Verify commission is linked to category
        $commission->refresh();
        $this->assertEquals($category->id, $commission->category_id);

        // Cleanup
        $commission->delete();
        CommissionCategoryUser::where('category_id', $category->id)->delete();
        $category->delete();
    }

    /**
     * Test commission with product-specific values
     */
    public function test_commission_with_product_values(): void
    {
        // Create commission
        $commission = Commission::create([
            'name' => 'Product Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 5,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        // Get a product
        $product = Product::first();

        if (!$product) {
            $this->markTestSkipped('No products available for testing');
        }

        // Add product-specific value
        $productValue = CommissionProductValue::create([
            'commission_id' => $commission->id,
            'product_id' => $product->id,
            'value' => 15, // Override: $15 for this specific product
            'author' => $this->users[0]->id,
        ]);

        $this->assertDatabaseHas('nexopos_commission_product_values', [
            'commission_id' => $commission->id,
            'product_id' => $product->id,
            'value' => 15,
        ]);

        // Verify the calculator uses product-specific value
        $calculatorService = app(CommissionCalculatorService::class);
        
        $calculatedValue = $calculatorService->calculateCommissionValue(
            $commission,
            100, // base amount
            1,   // quantity
            $product->id
        );

        // For fixed type with product value, should return 15
        $this->assertEquals(15, $calculatedValue);

        // Cleanup
        $productValue->delete();
        $commission->delete();
    }

    /**
     * Test earned commission record creation
     */
    public function test_earned_commission_creation(): void
    {
        // Create commission
        $commission = Commission::create([
            'name' => 'Earned Test Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 10,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $user = User::first();

        // Create earned commission manually (simulating order processing)
        $earned = EarnedCommission::create([
            'commission_id' => $commission->id,
            'commission_name' => $commission->name,
            'commission_type' => $commission->type,
            'commission_base' => $commission->base,
            'commission_value' => $commission->value,
            'user_id' => $user->id,
            'order_id' => 1, // Mock order ID
            'order_product_id' => 1, // Mock order product ID
            'product_id' => 1,
            'base_amount' => 100,
            'quantity' => 2,
            'value' => 20, // 2 * $10 fixed
            'author' => $this->users[0]->id,
        ]);

        $this->assertDatabaseHas('nexopos_earned_commissions', [
            'id' => $earned->id,
            'commission_id' => $commission->id,
            'user_id' => $user->id,
            'value' => 20,
        ]);

        // Cleanup
        $earned->delete();
        $commission->delete();
    }

    /**
     * Test commission soft deletes
     */
    public function test_commission_soft_delete(): void
    {
        $commission = Commission::create([
            'name' => 'Soft Delete Test ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 5,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $commissionId = $commission->id;

        // Soft delete
        $commission->delete();

        // Should not be found with normal query
        $this->assertNull(Commission::find($commissionId));

        // Should be found with trashed
        $this->assertNotNull(Commission::withTrashed()->find($commissionId));

        // Permanent delete
        Commission::withTrashed()->find($commissionId)->forceDelete();
    }

    /**
     * Test commission category with multiple users
     */
    public function test_category_with_multiple_users(): void
    {
        $category = CommissionCategory::create([
            'name' => 'Multi User Category ' . time(),
            'description' => 'Test category with multiple users',
            'author' => $this->users[0]->id,
        ]);

        // Add multiple users
        $users = User::limit(3)->get();

        foreach ($users as $user) {
            CommissionCategoryUser::create([
                'category_id' => $category->id,
                'user_id' => $user->id,
                'author' => $this->users[0]->id,
            ]);
        }

        // Verify all users are in category
        $this->assertEquals(
            $users->count(),
            CommissionCategoryUser::where('category_id', $category->id)->count()
        );

        // Cleanup
        CommissionCategoryUser::where('category_id', $category->id)->delete();
        $category->delete();
    }

    /**
     * Test commission type constants exist
     */
    public function test_commission_type_constants(): void
    {
        $this->assertEquals('on_the_house', Commission::TYPE_ON_THE_HOUSE);
        $this->assertEquals('fixed', Commission::TYPE_FIXED);
        $this->assertEquals('percentage', Commission::TYPE_PERCENTAGE);
    }

    /**
     * Test commission base constants exist
     */
    public function test_commission_base_constants(): void
    {
        $this->assertEquals('fixed', Commission::BASE_FIXED);
        $this->assertEquals('gross', Commission::BASE_GROSS);
        $this->assertEquals('net', Commission::BASE_NET);
    }

    /**
     * Test on the house commission value is always the fixed value
     */
    public function test_on_the_house_always_fixed_value(): void
    {
        $commission = Commission::create([
            'name' => 'On The House Test ' . time(),
            'type' => Commission::TYPE_ON_THE_HOUSE,
            'value' => 25, // Fixed $25 regardless of product price
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $calculatorService = app(CommissionCalculatorService::class);

        // Should return 25 regardless of base amount
        $value1 = $calculatorService->calculateCommissionValue($commission, 100, 1);
        $value2 = $calculatorService->calculateCommissionValue($commission, 500, 1);
        $value3 = $calculatorService->calculateCommissionValue($commission, 1000, 1);

        $this->assertEquals(25, $value1);
        $this->assertEquals(25, $value2);
        $this->assertEquals(25, $value3);

        // With quantity
        $value4 = $calculatorService->calculateCommissionValue($commission, 100, 3);
        $this->assertEquals(75, $value4); // 25 * 3

        // Cleanup
        $commission->delete();
    }

    /**
     * Test inactive commission is not processed
     */
    public function test_inactive_commission_not_processed(): void
    {
        $commission = Commission::create([
            'name' => 'Inactive Commission ' . time(),
            'type' => Commission::TYPE_PERCENTAGE,
            'value' => 10,
            'active' => false, // Inactive
            'author' => $this->users[0]->id,
        ]);

        $commissionService = app(CommissionService::class);
        
        // When commission is inactive, findApplicableCommission should not return it
        $applicable = $commissionService->findApplicableCommission($this->users[0]);
        
        if ($applicable) {
            $this->assertNotEquals($commission->id, $applicable->id);
        }

        // Cleanup
        $commission->delete();
    }

    /**
     * Test commission relationships
     */
    public function test_commission_relationships(): void
    {
        $category = CommissionCategory::create([
            'name' => 'Relationship Test Category ' . time(),
            'description' => 'Test category',
            'author' => $this->users[0]->id,
        ]);

        $commission = Commission::create([
            'name' => 'Relationship Test Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 10,
            'category_id' => $category->id,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        // Test commission -> category relationship
        $commission->refresh();
        $this->assertNotNull($commission->category);
        $this->assertEquals($category->id, $commission->category->id);

        // Test category -> commissions relationship
        $category->refresh();
        $this->assertTrue($category->commissions->contains($commission));

        // Cleanup
        $commission->delete();
        $category->delete();
    }
}
