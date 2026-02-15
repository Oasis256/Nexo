<?php

namespace Modules\RenCommissions\Http\Controllers;

use App\Http\Controllers\DashboardController as BaseDashboardController;
use App\Models\Product;
use App\Models\User;
use App\Services\DateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RenCommissions\Models\CommissionType;
use Modules\RenCommissions\Services\CommissionCalculatorService;
use Modules\RenCommissions\Services\CommissionSessionService;
use Modules\RenCommissions\Support\StoreContext;

class PosApiController extends BaseDashboardController
{
    public function __construct(
        DateService $dateService,
        private readonly CommissionSessionService $sessionService,
        private readonly CommissionCalculatorService $calculatorService
    ) {
        parent::__construct($dateService);
    }

    public function types(): JsonResponse
    {
        if (! $this->commissionsEnabled()) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => StoreContext::constrain(CommissionType::query())
                ->where('is_active', true)
                ->orderBy('priority')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function earners(): JsonResponse
    {
        if (! $this->commissionsEnabled()) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }

        $eligibleRoleIds = $this->eligibleRoleIds();

        $query = User::query()
            ->select(['id', 'username', 'email'])
            ->where('active', true)
            ->whereHas('roles.permissions', function ($query) {
                $query->where('nexopos_permissions.namespace', 'nexopos.rencommissions.earn');
            });

        if (! empty($eligibleRoleIds)) {
            $query->whereHas('roles', function ($query) use ($eligibleRoleIds) {
                $query->whereIn('nexopos_roles.id', $eligibleRoleIds);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => $query
                ->orderBy('username')
                ->get(),
        ]);
    }

    public function assign(Request $request): JsonResponse
    {
        if (! $this->commissionsEnabled()) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Commissions are disabled from settings.', 'RenCommissions'),
            ], 422);
        }

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
            'product_index' => ['required', 'integer', 'min:0'],
            'product_id' => ['required', 'integer', 'min:1'],
            'earner_id' => ['required', 'integer', 'min:1'],
            'type_id' => ['nullable', 'integer'],
            'commission_method' => ['required', 'in:fixed,percentage,on_the_house'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ]);

        if (! $this->earnerAllowed((int) $validated['earner_id'])) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Selected earner is not allowed to receive commissions.', 'RenCommissions'),
            ], 422);
        }

        $commissionValue = (float) $validated['commission_value'];

        // For fixed commissions, fallback to product.commission_value when client sends 0.
        if ($validated['commission_method'] === 'fixed' && $commissionValue <= 0) {
            $product = Product::query()
                ->select(['id', 'commission_value'])
                ->find((int) $validated['product_id']);

            $commissionValue = (float) ($product?->commission_value ?? 0);
        }

        if ($validated['commission_method'] !== 'on_the_house' && $commissionValue <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => __m('Commission value is 0. Set product/type commission first.', 'RenCommissions'),
            ], 422);
        }

        $total = $this->calculatorService->calculate(
            $validated['commission_method'],
            $commissionValue,
            (float) $validated['unit_price'],
            (float) $validated['quantity']
        );

        $session = $this->sessionService->assign([
            ...$validated,
            'commission_value' => $commissionValue,
            'total_commission' => $total,
            'assigned_by' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $session,
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
            'product_index' => ['required', 'integer', 'min:0'],
        ]);

        $deleted = $this->sessionService->remove($validated['session_id'], (int) $validated['product_index']);

        return response()->json([
            'status' => 'success',
            'data' => ['deleted' => $deleted],
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
        ]);

        $deleted = $this->sessionService->clear($validated['session_id']);

        return response()->json([
            'status' => 'success',
            'data' => ['deleted' => $deleted],
        ]);
    }

    private function commissionsEnabled(): bool
    {
        return ns()->option->get('rencommissions_enabled', 'yes') === 'yes';
    }

    private function eligibleRoleIds(): array
    {
        $value = ns()->option->get('rencommissions_eligible_roles', []);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_filter(array_map('trim', explode(',', $value)));
            }
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $value), fn ($id) => $id > 0)));
    }

    private function earnerAllowed(int $earnerId): bool
    {
        if ($earnerId <= 0) {
            return false;
        }

        $eligibleRoleIds = $this->eligibleRoleIds();

        $query = User::query()
            ->where('id', $earnerId)
            ->where('active', true)
            ->whereHas('roles.permissions', function ($query) {
                $query->where('nexopos_permissions.namespace', 'nexopos.rencommissions.earn');
            });

        if (! empty($eligibleRoleIds)) {
            $query->whereHas('roles', function ($query) use ($eligibleRoleIds) {
                $query->whereIn('nexopos_roles.id', $eligibleRoleIds);
            });
        }

        return $query->exists();
    }
}
