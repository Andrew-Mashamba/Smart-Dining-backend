<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderItemReady;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\OrderManagement\OrderDistributionService;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    protected OrderDistributionService $distributionService;

    public function __construct(OrderDistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * Mark item as received by kitchen/bar staff
     *
     * Validates that chefs can only update kitchen items and
     * bartenders can only update bar items.
     */
    public function markReceived(Request $request, $id)
    {
        $orderItem = OrderItem::with('menuItem')->findOrFail($id);
        $staff = $request->user();

        // Validate prep_area authorization for chefs and bartenders
        if (! $this->canUpdateItem($staff, $orderItem)) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        $this->distributionService->markItemReceived($orderItem, $staff);

        return response()->json([
            'message' => 'Order item marked as received',
            'item' => $orderItem->fresh(),
        ]);
    }

    /**
     * Mark item as done/ready
     *
     * Validates that chefs can only update kitchen items and
     * bartenders can only update bar items.
     */
    public function markDone(Request $request, $id)
    {
        $orderItem = OrderItem::with(['order', 'menuItem'])->findOrFail($id);
        $staff = $request->user();

        // Validate prep_area authorization for chefs and bartenders
        if (! $this->canUpdateItem($staff, $orderItem)) {
            return response()->json([
                'message' => 'Insufficient permissions',
            ], 403);
        }

        $this->distributionService->markItemReady($orderItem);

        event(new OrderItemReady($orderItem));

        return response()->json([
            'message' => 'Order item marked as ready',
            'item' => $orderItem->fresh(),
        ]);
    }

    /**
     * Check if staff member can update the order item based on prep_area
     *
     * - Chefs can only update kitchen items
     * - Bartenders can only update bar items
     * - Managers and admins can update all items
     */
    protected function canUpdateItem($staff, OrderItem $orderItem): bool
    {
        // Managers and admins have full access
        if (in_array($staff->role, ['manager', 'admin'])) {
            return true;
        }

        // Get the prep area from the menu item
        $prepArea = $orderItem->menuItem->prep_area;

        // Chefs can only update kitchen items
        if ($staff->role === 'chef' && $prepArea !== 'kitchen') {
            return false;
        }

        // Bartenders can only update bar items
        if ($staff->role === 'bartender' && $prepArea !== 'bar') {
            return false;
        }

        return true;
    }

    /**
     * Get pending items for current staff
     */
    public function pending(Request $request)
    {
        $staff = $request->user();

        if (! in_array($staff->role, ['chef', 'bartender'])) {
            return response()->json([
                'message' => 'Only chefs and bartenders can view pending items',
            ], 403);
        }

        $prepArea = $staff->role === 'chef' ? 'kitchen' : 'bar';

        $items = $this->distributionService->getPendingItems($prepArea);

        return response()->json([
            'items' => $items,
            'total' => $items->count(),
        ]);
    }
}
