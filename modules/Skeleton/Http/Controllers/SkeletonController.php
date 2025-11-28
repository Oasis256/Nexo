<?php

namespace Modules\Skeleton\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Services\DateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Skeleton\Crud\ItemsCrud;
use Modules\Skeleton\Models\SkeletonItem;
use Modules\Skeleton\Services\SkeletonService;

class SkeletonController extends DashboardController
{
    public function __construct(
        DateService $dateService,
        protected SkeletonService $skeletonService
    ) {
        parent::__construct($dateService);
    }

    /**
     * Dashboard/Home page for the Skeleton module
     */
    public function index()
    {
        return View::make('Skeleton::index', [
            'title' => __('Skeleton Module'),
            'description' => __('Welcome to the Skeleton module demonstration'),
        ]);
    }

    /**
     * Display list of items using CRUD
     */
    public function listItems()
    {
        return ItemsCrud::table();
    }

    /**
     * Create new item form
     */
    public function createItem()
    {
        return ItemsCrud::form();
    }

    /**
     * Edit existing item form
     */
    public function editItem(SkeletonItem $item)
    {
        return ItemsCrud::form($item);
    }

    /**
     * Custom page demonstrating various features
     */
    public function featuresPage()
    {
        return View::make('Skeleton::features', [
            'title' => __('Module Features'),
            'description' => __('Demonstration of various NexoPOS features'),
            'stats' => $this->skeletonService->getStats(),
        ]);
    }

    /**
     * API endpoint example
     */
    public function getItemsApi(Request $request)
    {
        $items = SkeletonItem::query();

        if ($request->has('search')) {
            $items->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json([
            'status' => 'success',
            'data' => $items->paginate(10),
        ]);
    }

    /**
     * Example of form submission
     */
    public function submitAction(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $result = $this->skeletonService->processAction($request->all());

        return response()->json([
            'status' => 'success',
            'message' => __('Action processed successfully'),
            'data' => $result,
        ]);
    }

    /**
     * Settings page
     */
    public function settings()
    {
        return View::make('Skeleton::settings', [
            'title' => __('Skeleton Settings'),
            'description' => __('Configure the Skeleton module'),
        ]);
    }
}
