<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Services\OrderManagement\OrderDistributionService;

class KitchenController extends Controller
{
    protected OrderDistributionService $distributionService;

    public function __construct(OrderDistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    public function display()
    {
        $pendingItems = OrderItem::whereHas('menuItem', function ($query) {
            $query->where('prep_area', 'kitchen');
        })
            ->where('status', 'confirmed')
            ->with(['order.table', 'menuItem'])
            ->orderBy('created_at', 'asc')
            ->get();

        $preparingItems = OrderItem::whereHas('menuItem', function ($query) {
            $query->where('prep_area', 'kitchen');
        })
            ->where('status', 'preparing')
            ->with(['order.table', 'menuItem', 'preparedBy'])
            ->orderBy('updated_at', 'asc')
            ->get();

        $readyItems = OrderItem::whereHas('menuItem', function ($query) {
            $query->where('prep_area', 'kitchen');
        })
            ->where('status', 'ready')
            ->with(['order.table', 'menuItem'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('kitchen.display', compact('pendingItems', 'preparingItems', 'readyItems'));
    }

    public function markReceived($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        $this->distributionService->markItemReceived($orderItem, auth()->user());

        return redirect()->back()->with('success', 'Item marked as received');
    }

    public function markDone($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        $this->distributionService->markItemReady($orderItem);

        return redirect()->back()->with('success', 'Item marked as ready');
    }
}
