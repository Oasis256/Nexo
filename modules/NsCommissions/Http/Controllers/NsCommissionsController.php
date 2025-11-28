<?php

namespace Modules\NsCommissions\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\NsCommissions\Crud\CommissionsCrud;
use Modules\NsCommissions\Crud\EarnedCommissionCrud;
use Modules\NsCommissions\Models\Commission;
use Modules\NsCommissions\Models\CommissionProductValue;
use Modules\NsCommissions\Models\EarnedCommission;
use Modules\NsCommissions\Services\CommissionCalculatorService;
use Modules\NsCommissions\Settings\CommissionsSettings;

class NsCommissionsController extends DashboardController
{
    public function listCommissions(Request $request)
    {
        return CommissionsCrud::table();
    }

    public function createCommissions(Request $request)
    {
        return CommissionsCrud::form();
    }

    public function updateCommissions(Commission $id, Request $request)
    {
        return CommissionsCrud::form($id);
    }

    public function getEarnedCommissions()
    {
        return EarnedCommissionCrud::table();
    }

    public function createEarnedCommissions()
    {
        return EarnedCommissionCrud::form();
    }

    public function updateEarnedCommissions(EarnedCommission $id)
    {
        return EarnedCommissionCrud::form($id);
    }

    public function getCommsisionsReport()
    {
        return $this->view('NsCommissions::reports.commissions', [
            'title'     =>      __m('Commissions Report', 'NsCommissions'),
        ]);
    }

    public function getCommissionsSettings()
    {
        return CommissionsSettings::renderForm();
    }

    public function getReportData(Request $request)
    {
        $rolesIds = ns()->option->get('ns_commissions_roles');
        $roles = Role::whereIn('id', $rolesIds)->get();
        $users = $roles->map(function ($role) {
            return $role->users;
        })->flatten();

        return $users->map(function ($user) use ($request) {
            $commissions = EarnedCommission::where('user_id', $user->id)
                ->with('order')
                ->where(function ($query) use ($request) {
                    $query->where('created_at', '>=', Carbon::parse($request->input('startDate'))->toDateTimeString());
                    $query->where('created_at', '<=', Carbon::parse($request->input('endDate'))->toDateTimeString());
                });

            $user->commissions = $commissions->sum('value');
            $orderId = $commissions->get('order_id')->pluck('order_id');
            $user->total_sales = Order::whereIn('id', $orderId)->sum('total');
            $user->total_sales_count = count($orderId);

            return $user;
        });
    }

    /**
     * Get eligible users who can earn commission for a product
     * Used in POS for per-item user selection
     */
    public function getEligibleUsers(Request $request)
    {
        $productCategoryId = $request->input('category_id');
        
        $service = app(CommissionCalculatorService::class);
        $users = $service->getEligibleCommissionUsers($productCategoryId);

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * Preview commission for a cart item
     * Called from POS to show expected commission
     */
    public function previewCommission(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'category_id' => 'required|integer',
            'unit_price' => 'required|numeric',
            'quantity' => 'required|numeric',
            'user_id' => 'required|integer',
        ]);

        $service = app(CommissionCalculatorService::class);
        $preview = $service->previewCommission(
            productId: $request->input('product_id'),
            productCategoryId: $request->input('category_id'),
            unitPrice: $request->input('unit_price'),
            quantity: $request->input('quantity'),
            userId: $request->input('user_id')
        );

        return response()->json([
            'status' => 'success',
            'data' => $preview,
        ]);
    }

    /**
     * Assign user to earn commission for order product
     */
    public function assignCommissionUser(Request $request, Order $order, OrderProduct $orderProduct)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:nexopos_users,id',
            'commission_id' => 'nullable|integer|exists:nexopos_commissions,id',
        ]);

        $service = app(CommissionCalculatorService::class);
        $assignment = $service->assignCommissionUser(
            orderId: $order->id,
            orderProductId: $orderProduct->id,
            userId: $request->input('user_id'),
            commissionId: $request->input('commission_id')
        );

        return response()->json([
            'status' => 'success',
            'message' => __m('Commission user assigned successfully.', 'NsCommissions'),
            'data' => $assignment,
        ]);
    }

    /**
     * Get product values for a commission (for Fixed type)
     */
    public function getCommissionProductValues(Commission $commission)
    {
        $productValues = $commission->productValues()->with('product:id,name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $productValues,
        ]);
    }

    /**
     * Save product values for a commission
     */
    public function saveCommissionProductValues(Request $request, Commission $commission)
    {
        $request->validate([
            'product_values' => 'required|array',
            'product_values.*.product_id' => 'required|integer|exists:nexopos_products,id',
            'product_values.*.value' => 'required|numeric|min:0',
        ]);

        // Delete existing values
        CommissionProductValue::where('commission_id', $commission->id)->delete();

        // Create new values
        foreach ($request->input('product_values') as $productValue) {
            CommissionProductValue::create([
                'commission_id' => $commission->id,
                'product_id' => $productValue['product_id'],
                'value' => $productValue['value'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => __m('Product values saved successfully.', 'NsCommissions'),
        ]);
    }

    /**
     * Search products for commission product values
     */
    public function searchProducts(Request $request)
    {
        $search = $request->input('search', '');
        $categoryIds = $request->input('category_ids', []);

        $query = Product::query()
            ->select(['id', 'name', 'sku', 'category_id'])
            ->where('type', '!=', 'grouped');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        $products = $query->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }
}
