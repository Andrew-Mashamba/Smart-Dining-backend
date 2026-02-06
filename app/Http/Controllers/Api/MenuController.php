<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Menu\MenuService;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    /**
     * Get all available menu items
     */
    public function index(Request $request)
    {
        $filters = $request->only(['category', 'prep_area', 'max_price', 'min_price']);

        $menu = $this->menuService->getAvailableMenu($filters);

        return response()->json([
            'items' => $menu,
            'total' => $menu->count(),
        ]);
    }

    /**
     * Get menu items (optionally filtered by category)
     */
    public function items(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:menu_categories,id',
        ]);

        $query = \App\Models\MenuItem::query()->where('available', true);

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $items = $query->with('category')->get();

        return response()->json([
            'items' => \App\Http\Resources\MenuItemResource::collection($items),
            'total' => $items->count(),
        ]);
    }

    /**
     * Get menu items by category
     */
    public function categories()
    {
        $menuByCategory = $this->menuService->getMenuByCategory();

        return response()->json([
            'categories' => $menuByCategory,
        ]);
    }

    /**
     * Get a specific menu item
     */
    public function show($id)
    {
        $item = $this->menuService->getMenuItem($id);

        return response()->json($item);
    }

    /**
     * Search menu items
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $results = $this->menuService->searchMenu($request->query);

        return response()->json([
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    /**
     * Get popular menu items
     */
    public function popular(Request $request)
    {
        $limit = $request->input('limit', 10);

        $popularItems = $this->menuService->getPopularItems($limit);

        return response()->json([
            'items' => $popularItems,
        ]);
    }

    /**
     * Update menu item availability
     */
    public function updateAvailability(Request $request, $id)
    {
        $request->validate([
            'available' => 'required|boolean',
        ]);

        $item = $this->menuService->getMenuItem($id);

        $this->menuService->updateAvailability($item, $request->available);

        return response()->json([
            'message' => 'Menu item availability updated successfully',
            'item' => $item->fresh(),
        ]);
    }

    /**
     * Get menu statistics
     */
    public function stats()
    {
        $stats = $this->menuService->getMenuStats();

        return response()->json($stats);
    }
}
