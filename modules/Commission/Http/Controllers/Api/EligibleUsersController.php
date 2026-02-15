<?php

namespace Modules\Commission\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Commission\Services\CommissionCalculatorService;

/**
 * API Controller for getting eligible commission users in POS
 */
class EligibleUsersController extends Controller
{
    public function __construct(
        protected CommissionCalculatorService $calculatorService
    ) {
    }

    /**
     * Get users eligible to receive commission for a product
     */
    public function getEligibleUsers(Product $product): JsonResponse
    {
        $users = $this->calculatorService->getEligibleCommissionUsers($product->category_id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatUsers($users),
        ]);
    }

    /**
     * Get users eligible to receive commission by category ID (query param)
     */
    public function getEligibleUsersByCategory(Request $request): JsonResponse
    {
        $categoryId = $request->query('category_id');

        if (!$categoryId) {
            return response()->json([
                'status' => 'error',
                'message' => __('Category ID is required'),
                'data' => [],
            ], 400);
        }

        $users = $this->calculatorService->getEligibleCommissionUsers((int) $categoryId);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatUsers($users),
        ]);
    }

    /**
     * Format user collection for response
     */
    protected function formatUsers($users): array
    {
        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->attribute?->first_name ?? '',
                'last_name' => $user->attribute?->last_name ?? '',
                'avatar' => $user->attribute?->avatar ?? null,
            ];
        })->toArray();
    }
}
