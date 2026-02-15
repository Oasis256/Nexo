<?php

namespace Modules\Skeleton\Services;

use Modules\Skeleton\Models\SkeletonItem;

class SkeletonService
{
    /**
     * Get statistics for dashboard
     */
    public function getStats(): array
    {
        return [
            'total_items' => SkeletonItem::count(),
            'active_items' => SkeletonItem::active()->count(),
            'total_value' => SkeletonItem::sum('price'),
            'categories' => SkeletonItem::distinct('category')->count('category'),
        ];
    }

    /**
     * Process a custom action
     */
    public function processAction(array $data): array
    {
        // Example business logic
        $item = SkeletonItem::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => SkeletonItem::STATUS_ACTIVE,
            'category' => $data['category'] ?? 'general',
            'price' => $data['price'] ?? 0,
            'quantity' => $data['quantity'] ?? 0,
        ]);

        return [
            'item_id' => $item->id,
            'created_at' => $item->created_at->toDateTimeString(),
        ];
    }

    /**
     * Calculate total inventory value
     */
    public function calculateInventoryValue(): float
    {
        return SkeletonItem::active()
            ->get()
            ->sum(fn($item) => $item->price * $item->quantity);
    }

    /**
     * Get items by category
     */
    public function getItemsByCategory(string $category): array
    {
        return SkeletonItem::category($category)
            ->active()
            ->get()
            ->toArray();
    }
}
