<?php

namespace App\Services\Menu;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Support\Collection;

class MenuService
{
    /**
     * Get available menu items with optional filters
     */
    public function getAvailableMenu(array $filters = []): Collection
    {
        $query = MenuItem::where('status', 'available');

        if (isset($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        if (isset($filters['prep_area'])) {
            $query->where('prep_area', $filters['prep_area']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        return $query->orderBy('category_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get menu items by category
     */
    public function getItemsByCategory(int $categoryId): Collection
    {
        return MenuItem::where('category_id', $categoryId)
            ->where('status', 'available')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get menu items by preparation area
     */
    public function getItemsByPrepArea(string $prepArea): Collection
    {
        return MenuItem::where('prep_area', $prepArea)
            ->where('status', 'available')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();
    }

    /**
     * Update menu item availability
     */
    public function updateAvailability(MenuItem $item, bool $available): void
    {
        $item->update(['status' => $available ? 'available' : 'unavailable']);

        \Log::info('Menu item availability updated', [
            'item_id' => $item->id,
            'name' => $item->name,
            'available' => $available,
        ]);
    }

    /**
     * Get menu grouped by category
     */
    public function getMenuByCategory(): array
    {
        $categories = MenuCategory::where('status', 'active')
            ->with(['menuItems' => function ($query) {
                $query->where('status', 'available')->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        return $categories->filter(fn ($cat) => $cat->menuItems->isNotEmpty())
            ->map(function ($category) {
                return [
                    'category' => $category->name,
                    'items' => $category->menuItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'price' => $item->price,
                            'prep_time_minutes' => $item->prep_time_minutes,
                            'prep_area' => $item->prep_area,
                        ];
                    }),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Search menu items by name or description
     */
    public function searchMenu(string $searchTerm): Collection
    {
        return MenuItem::where('status', 'available')
            ->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Get menu item with details
     */
    public function getMenuItem(int $itemId): MenuItem
    {
        return MenuItem::findOrFail($itemId);
    }

    /**
     * Get popular menu items based on order count
     */
    public function getPopularItems(int $limit = 10): Collection
    {
        return MenuItem::where('status', 'available')
            ->withCount('orderItems')
            ->orderBy('order_items_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk update menu item availability
     */
    public function bulkUpdateAvailability(array $itemIds, bool $available): int
    {
        return MenuItem::whereIn('id', $itemIds)
            ->update(['status' => $available ? 'available' : 'unavailable']);
    }

    /**
     * Get menu statistics
     */
    public function getMenuStats(): array
    {
        $total = MenuItem::count();
        $available = MenuItem::where('status', 'available')->count();
        $byCategory = MenuItem::join('menu_categories', 'menu_items.category_id', '=', 'menu_categories.id')
            ->select('menu_categories.name as category')
            ->selectRaw('count(*) as count')
            ->groupBy('menu_categories.name')
            ->pluck('count', 'category')
            ->toArray();

        return [
            'total_items' => $total,
            'available_items' => $available,
            'unavailable_items' => $total - $available,
            'by_category' => $byCategory,
        ];
    }
}
