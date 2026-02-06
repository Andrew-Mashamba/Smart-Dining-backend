<?php

namespace App\Livewire;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Staff;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Render the dashboard with comprehensive metrics.
     */
    public function render()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Today's metrics - select only needed columns for performance
        $todayOrders = Order::select('id', 'total', 'created_at')
            ->whereDate('created_at', $today)
            ->get();
        $yesterdayOrders = Order::select('id', 'total', 'created_at')
            ->whereDate('created_at', $yesterday)
            ->get();

        $todayOrdersCount = $todayOrders->count();
        $yesterdayOrdersCount = $yesterdayOrders->count();
        $ordersChange = $this->calculatePercentageChange($yesterdayOrdersCount, $todayOrdersCount);

        // Revenue metrics
        $todayRevenue = $todayOrders->sum('total');
        $yesterdayRevenue = $yesterdayOrders->sum('total');
        $revenueChange = $this->calculatePercentageChange($yesterdayRevenue, $todayRevenue);

        // Active tables (occupied status)
        $activeTables = Table::where('status', 'occupied')->count();
        $yesterdayActiveTables = $this->getYesterdayActiveTables();
        $activeTablesChange = $this->calculatePercentageChange($yesterdayActiveTables, $activeTables);

        // Average order value
        $avgOrderValue = $todayOrdersCount > 0 ? $todayRevenue / $todayOrdersCount : 0;
        $yesterdayAvgOrderValue = $yesterdayOrdersCount > 0 ? $yesterdayRevenue / $yesterdayOrdersCount : 0;
        $avgOrderValueChange = $this->calculatePercentageChange($yesterdayAvgOrderValue, $avgOrderValue);

        // Active orders count (pending, preparing, ready)
        $activeOrdersCount = Order::whereIn('status', ['pending', 'preparing', 'ready'])->count();

        // Recent orders - last 10 with eager loading
        $recentOrders = Order::select('id', 'order_number', 'table_id', 'waiter_id', 'status', 'total', 'created_at')
            ->with(['table:id,name', 'waiter:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Low stock alerts - items below threshold with limited columns
        $lowStockItems = MenuItem::select('id', 'name', 'stock_quantity', 'unit', 'low_stock_threshold')
            ->whereRaw('stock_quantity < low_stock_threshold')
            ->orWhere('stock_quantity', '<=', 0)
            ->orderBy('stock_quantity', 'asc')
            ->get();

        // Revenue chart data - last 7 days
        $revenueChartData = $this->getRevenueChartData();

        // Top selling items today
        $topSellingItems = $this->getTopSellingItemsToday();

        // Staff on duty (active status) with limited columns
        $staffOnDuty = Staff::select('id', 'name', 'role', 'status')
            ->where('status', 'active')
            ->get();

        return view('livewire.dashboard', [
            // Today's metrics
            'todayOrdersCount' => $todayOrdersCount,
            'todayRevenue' => $todayRevenue,
            'activeTables' => $activeTables,
            'avgOrderValue' => $avgOrderValue,

            // Comparison metrics
            'ordersChange' => $ordersChange,
            'revenueChange' => $revenueChange,
            'activeTablesChange' => $activeTablesChange,
            'avgOrderValueChange' => $avgOrderValueChange,

            // Widgets data
            'activeOrdersCount' => $activeOrdersCount,
            'recentOrders' => $recentOrders,
            'lowStockItems' => $lowStockItems,
            'revenueChartData' => $revenueChartData,
            'topSellingItems' => $topSellingItems,
            'staffOnDuty' => $staffOnDuty,
        ])->layout('layouts.app-layout');
    }

    /**
     * Calculate percentage change between two values.
     */
    private function calculatePercentageChange($oldValue, $newValue): array
    {
        if ($oldValue == 0) {
            if ($newValue > 0) {
                return ['value' => 100, 'direction' => 'up'];
            }

            return ['value' => 0, 'direction' => 'neutral'];
        }

        $change = (($newValue - $oldValue) / $oldValue) * 100;
        $direction = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral');

        return [
            'value' => abs(round($change, 1)),
            'direction' => $direction,
        ];
    }

    /**
     * Get yesterday's active tables count (approximation based on orders).
     */
    private function getYesterdayActiveTables(): int
    {
        $yesterday = Carbon::yesterday();

        return Order::whereDate('created_at', $yesterday)
            ->distinct('table_id')
            ->count('table_id');
    }

    /**
     * Get revenue data for the last 7 days.
     */
    private function getRevenueChartData(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenue = Order::whereDate('created_at', $date)->sum('total');
            $data[] = [
                'date' => $date->format('M d'),
                'revenue' => (float) $revenue,
            ];
        }

        return $data;
    }

    /**
     * Get top selling items today with count and revenue.
     */
    private function getTopSellingItemsToday(): array
    {
        $today = Carbon::today();

        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->whereDate('orders.created_at', $today)
            ->select(
                'menu_items.name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.subtotal) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'count' => (int) $item->total_quantity,
                    'revenue' => (float) $item->total_revenue,
                ];
            })
            ->toArray();
    }
}
