<?php

namespace App\Livewire;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SalesReports extends Component
{
    public $start_date;

    public $end_date;

    public function mount()
    {
        // Set default date range (current month)
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    /**
     * Get summary statistics for the date range
     * Optimized to select only needed columns and use single query
     */
    public function getSummaryStats()
    {
        $stats = Order::select(DB::raw('COUNT(*) as total_orders, SUM(total) as total_revenue, SUM(tax) as total_tax'))
            ->whereBetween('created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->where('status', '!=', 'cancelled')
            ->first();

        return [
            'total_revenue' => (float) ($stats->total_revenue ?? 0),
            'total_orders' => (int) ($stats->total_orders ?? 0),
            'average_order_value' => $stats->total_orders > 0 ? $stats->total_revenue / $stats->total_orders : 0,
            'total_tax' => (float) ($stats->total_tax ?? 0),
        ];
    }

    /**
     * Get revenue breakdown by category
     */
    public function getRevenueByCategory()
    {
        return MenuCategory::select('menu_categories.id', 'menu_categories.name')
            ->leftJoin('menu_items', 'menu_categories.id', '=', 'menu_items.category_id')
            ->leftJoin('order_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_categories.id', 'menu_categories.name')
            ->selectRaw('COALESCE(SUM(order_items.subtotal), 0) as total_revenue')
            ->orderByDesc('total_revenue')
            ->get();
    }

    /**
     * Get revenue breakdown by payment method
     */
    public function getRevenueByPaymentMethod()
    {
        return Payment::select('payment_method')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->whereBetween('payments.created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->where('payments.status', 'completed')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('payment_method')
            ->selectRaw('SUM(payments.amount) as total_amount')
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * Get daily revenue data for the chart
     */
    public function getDailyRevenue()
    {
        $dailyRevenue = Order::select(DB::raw('DATE(created_at) as date'))
            ->whereBetween('created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->where('status', '!=', 'cancelled')
            ->groupBy('date')
            ->selectRaw('SUM(total) as revenue')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $dailyRevenue->pluck('date')->map(fn ($date) => date('M d', strtotime($date)))->toArray(),
            'data' => $dailyRevenue->pluck('revenue')->toArray(),
        ];
    }

    /**
     * Get top selling items
     */
    public function getTopSellingItems($limit = 10)
    {
        return MenuItem::select('menu_items.id', 'menu_items.name', 'menu_items.price')
            ->join('order_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween('orders.created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.price')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.subtotal) as total_revenue')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Export report to PDF
     */
    public function exportPdf()
    {
        $data = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'summary' => $this->getSummaryStats(),
            'revenue_by_category' => $this->getRevenueByCategory(),
            'revenue_by_payment' => $this->getRevenueByPaymentMethod(),
            'daily_revenue' => $this->getDailyRevenue(),
            'top_items' => $this->getTopSellingItems(),
        ];

        $pdf = Pdf::loadView('reports.sales-pdf', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'sales-report-'.$this->start_date.'-to-'.$this->end_date.'.pdf');
    }

    /**
     * Export report to CSV
     * Optimized with eager loading and column selection
     */
    public function exportCsv()
    {
        $orders = Order::select('id', 'order_number', 'total', 'tax', 'status', 'created_at')
            ->with([
                'orderItems:id,order_id,menu_item_id,quantity,unit_price,subtotal',
                'orderItems.menuItem:id,name',
                'payments:id,order_id,payment_method',
            ])
            ->whereBetween('created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->where('status', '!=', 'cancelled')
            ->get();

        $filename = 'sales-report-'.$this->start_date.'-to-'.$this->end_date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Order Number',
                'Date',
                'Item Name',
                'Quantity',
                'Unit Price',
                'Subtotal',
                'Order Total',
                'Tax',
                'Payment Method',
                'Status',
            ]);

            // Add data rows
            foreach ($orders as $order) {
                foreach ($order->orderItems as $item) {
                    $paymentMethod = $order->payments->first()?->payment_method ?? 'N/A';

                    fputcsv($file, [
                        $order->order_number,
                        $order->created_at->format('Y-m-d H:i:s'),
                        $item->menuItem->name ?? 'Unknown',
                        $item->quantity,
                        number_format($item->unit_price, 2),
                        number_format($item->subtotal, 2),
                        number_format($order->total, 2),
                        number_format($order->tax, 2),
                        $paymentMethod,
                        $order->status,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $summary = $this->getSummaryStats();
        $revenueByCategory = $this->getRevenueByCategory();
        $revenueByPayment = $this->getRevenueByPaymentMethod();
        $dailyRevenue = $this->getDailyRevenue();
        $topItems = $this->getTopSellingItems();

        return view('livewire.sales-reports', compact(
            'summary',
            'revenueByCategory',
            'revenueByPayment',
            'dailyRevenue',
            'topItems'
        ))->layout('layouts.app-layout');
    }
}
