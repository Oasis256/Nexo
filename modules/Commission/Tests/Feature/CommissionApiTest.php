<?php

namespace Modules\Commission\Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Modules\Commission\Models\Commission;
use Modules\Commission\Models\CommissionCategory;
use Modules\Commission\Models\EarnedCommission;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class CommissionApiTest extends TestCase
{
    use WithAuthentication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attemptAuthenticate();
    }

    /**
     * Test get all commissions endpoint
     */
    public function test_can_get_all_commissions(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'type',
                    'value',
                    'active',
                ]
            ]
        ]);
    }

    /**
     * Test create commission endpoint
     */
    public function test_can_create_commission(): void
    {
        $commissionData = [
            'name' => 'Test Commission ' . time(),
            'type' => Commission::TYPE_PERCENTAGE,
            'value' => 10,
            'base' => Commission::BASE_GROSS,
            'active' => true,
            'description' => 'Test commission description',
        ];

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', '/api/commissions', $commissionData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'id',
                'name',
                'type',
                'value',
            ]
        ]);

        // Cleanup
        Commission::where('name', $commissionData['name'])->delete();
    }

    /**
     * Test update commission endpoint
     */
    public function test_can_update_commission(): void
    {
        // Create a commission first
        $commission = Commission::create([
            'name' => 'Test Update Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 5,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $updateData = [
            'name' => 'Updated Commission Name',
            'value' => 15,
        ];

        $response = $this->withSession($this->app['session']->all())
            ->json('PUT', '/api/commissions/' . $commission->id, $updateData);

        $response->assertStatus(200);

        // Verify update
        $commission->refresh();
        $this->assertEquals('Updated Commission Name', $commission->name);
        $this->assertEquals(15, $commission->value);

        // Cleanup
        $commission->delete();
    }

    /**
     * Test delete commission endpoint
     */
    public function test_can_delete_commission(): void
    {
        // Create a commission first
        $commission = Commission::create([
            'name' => 'Test Delete Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 5,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $response = $this->withSession($this->app['session']->all())
            ->json('DELETE', '/api/commissions/' . $commission->id);

        $response->assertStatus(200);

        // Verify deletion
        $this->assertNull(Commission::find($commission->id));
    }

    /**
     * Test get eligible users endpoint
     */
    public function test_can_get_eligible_users(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/eligible-users');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'username',
                ]
            ]
        ]);
    }

    /**
     * Test get eligible users by category
     */
    public function test_can_get_eligible_users_by_category(): void
    {
        // Create category first
        $category = CommissionCategory::create([
            'name' => 'Test Category ' . time(),
            'description' => 'Test category description',
            'author' => $this->users[0]->id,
        ]);

        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/eligible-users/' . $category->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'
        ]);

        // Cleanup
        $category->delete();
    }

    /**
     * Test get statistics endpoint
     */
    public function test_can_get_statistics(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'totalEarnings',
                'topEarners',
                'recentCommissions',
                'dailyEarnings',
            ]
        ]);
    }

    /**
     * Test get statistics with date filters
     */
    public function test_can_get_statistics_with_date_filter(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/statistics', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'totalEarnings',
                'topEarners',
                'recentCommissions',
                'dailyEarnings',
            ]
        ]);
    }

    /**
     * Test get product values endpoint
     */
    public function test_can_get_product_values(): void
    {
        // Create a commission first
        $commission = Commission::create([
            'name' => 'Test Product Values Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 5,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/' . $commission->id . '/product-values');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'
        ]);

        // Cleanup
        $commission->delete();
    }

    /**
     * Test search products endpoint
     */
    public function test_can_search_products(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/products/search', [
                'search' => 'test',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data'
        ]);
    }

    /**
     * Test export payroll endpoint
     */
    public function test_can_export_payroll(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/export/payroll', [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ]);

        // Should return CSV or JSON
        $this->assertTrue(in_array($response->status(), [200, 204]));
    }

    /**
     * Test unauthorized access is blocked
     */
    public function test_unauthenticated_access_blocked(): void
    {
        // Logout
        auth()->logout();

        $response = $this->json('GET', '/api/commissions');

        $response->assertStatus(401);
    }

    /**
     * Test create commission validation
     */
    public function test_create_commission_validation(): void
    {
        $invalidData = [
            // Missing required fields
        ];

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', '/api/commissions', $invalidData);

        $response->assertStatus(422);
    }

    /**
     * Test invalid commission type rejected
     */
    public function test_invalid_commission_type_rejected(): void
    {
        $invalidData = [
            'name' => 'Invalid Type Commission',
            'type' => 'invalid_type',
            'value' => 10,
        ];

        $response = $this->withSession($this->app['session']->all())
            ->json('POST', '/api/commissions', $invalidData);

        $response->assertStatus(422);
    }

    /**
     * Test commission not found returns 404
     */
    public function test_commission_not_found(): void
    {
        $response = $this->withSession($this->app['session']->all())
            ->json('GET', '/api/commissions/99999');

        $response->assertStatus(404);
    }

    /**
     * Test can toggle commission active status
     */
    public function test_can_toggle_commission_status(): void
    {
        // Create a commission first
        $commission = Commission::create([
            'name' => 'Test Toggle Commission ' . time(),
            'type' => Commission::TYPE_FIXED,
            'value' => 5,
            'active' => true,
            'author' => $this->users[0]->id,
        ]);

        $response = $this->withSession($this->app['session']->all())
            ->json('PUT', '/api/commissions/' . $commission->id, [
                'active' => false,
            ]);

        $response->assertStatus(200);

        $commission->refresh();
        $this->assertFalse((bool) $commission->active);

        // Cleanup
        $commission->delete();
    }
}
