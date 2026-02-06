<?php

namespace App\Livewire;

use App\Models\InventoryTransaction;
use App\Models\MenuItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InventoryReports extends Component
{
    public $start_date;

    public $end_date;

    public $filter_menu_item_id = '';

    public $filter_transaction_type = '';

    public function mount()
    {
        // Set default date range (current month)
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    /**
     * Get current stock summary for all menu items
     */
    public function getCurrentStock()
    {
        return MenuItem::select('id', 'name', 'stock_quantity', 'unit', 'low_stock_threshold', 'price')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get items with low stock (below threshold)
     */
    public function getLowStockItems()
    {
        return MenuItem::whereColumn('stock_quantity', '<', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->get();
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems()
    {
        return MenuItem::where('stock_quantity', '=', 0)
            ->orderBy('name')
            ->get();
    }

    /**
     * Calculate total inventory value
     */
    public function getTotalInventoryValue()
    {
        return MenuItem::selectRaw('SUM(stock_quantity * price) as total_value')
            ->first()
            ->total_value ?? 0;
    }

    /**
     * Get transaction summary by type for date range
     */
    public function getTransactionSummary()
    {
        $transactions = InventoryTransaction::whereBetween('created_at', [
            $this->start_date.' 00:00:00',
            $this->end_date.' 23:59:59',
        ])
            ->select('transaction_type')
            ->selectRaw('SUM(CASE WHEN transaction_type = "restock" THEN quantity ELSE 0 END) as total_restocks')
            ->selectRaw('SUM(CASE WHEN transaction_type = "sale" THEN quantity ELSE 0 END) as total_sales')
            ->selectRaw('SUM(CASE WHEN transaction_type = "adjustment" THEN quantity ELSE 0 END) as total_adjustments')
            ->selectRaw('SUM(CASE WHEN transaction_type = "waste" THEN quantity ELSE 0 END) as total_waste')
            ->first();

        return [
            'total_restocks' => $transactions->total_restocks ?? 0,
            'total_sales' => $transactions->total_sales ?? 0,
            'total_adjustments' => $transactions->total_adjustments ?? 0,
            'total_waste' => $transactions->total_waste ?? 0,
        ];
    }

    /**
     * Get transaction history with filters
     */
    public function getTransactionHistory()
    {
        $query = InventoryTransaction::with(['menuItem', 'createdBy'])
            ->whereBetween('inventory_transactions.created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ]);

        // Apply menu item filter
        if ($this->filter_menu_item_id) {
            $query->where('menu_item_id', $this->filter_menu_item_id);
        }

        // Apply transaction type filter
        if ($this->filter_transaction_type) {
            $query->where('transaction_type', $this->filter_transaction_type);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
    }

    /**
     * Get inventory turnover rate data for chart
     * Calculates the rate at which inventory is used/sold over time
     */
    public function getInventoryTurnover()
    {
        // Get daily transaction data
        $dailyData = InventoryTransaction::select(DB::raw('DATE(created_at) as date'))
            ->whereBetween('created_at', [
                $this->start_date.' 00:00:00',
                $this->end_date.' 23:59:59',
            ])
            ->groupBy('date')
            ->selectRaw('SUM(CASE WHEN transaction_type IN ("sale", "waste") THEN quantity ELSE 0 END) as usage')
            ->selectRaw('SUM(CASE WHEN transaction_type = "restock" THEN quantity ELSE 0 END) as restocks')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $dailyData->pluck('date')->map(fn ($date) => date('M d', strtotime($date)))->toArray(),
            'usage_data' => $dailyData->pluck('usage')->toArray(),
            'restock_data' => $dailyData->pluck('restocks')->toArray(),
        ];
    }

    /**
     * Get all menu items for filter dropdown
     */
    public function getMenuItemsForFilter()
    {
        return MenuItem::orderBy('name')->get();
    }

    /**
     * Export report to PDF
     */
    public function exportPdf()
    {
        $data = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'current_stock' => $this->getCurrentStock(),
            'low_stock' => $this->getLowStockItems(),
            'out_of_stock' => $this->getOutOfStockItems(),
            'total_value' => $this->getTotalInventoryValue(),
            'summary' => $this->getTransactionSummary(),
            'transactions' => $this->getTransactionHistory(),
        ];

        $pdf = Pdf::loadView('reports.inventory-pdf', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inventory-report-'.$this->start_date.'-to-'.$this->end_date.'.pdf');
    }

    /**
     * Export report to CSV
     */
    public function exportCsv()
    {
        $transactions = $this->getTransactionHistory();
        $filename = 'inventory-report-'.$this->start_date.'-to-'.$this->end_date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Date',
                'Menu Item',
                'Transaction Type',
                'Quantity',
                'Unit',
                'Created By',
                'Notes',
            ]);

            // Add data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->menuItem->name ?? 'Unknown',
                    ucfirst($transaction->transaction_type),
                    $transaction->quantity,
                    $transaction->unit,
                    $transaction->createdBy->name ?? 'Unknown',
                    $transaction->notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $currentStock = $this->getCurrentStock();
        $lowStockItems = $this->getLowStockItems();
        $outOfStockItems = $this->getOutOfStockItems();
        $totalValue = $this->getTotalInventoryValue();
        $summary = $this->getTransactionSummary();
        $transactions = $this->getTransactionHistory();
        $turnoverData = $this->getInventoryTurnover();
        $menuItems = $this->getMenuItemsForFilter();

        return view('livewire.inventory-reports', compact(
            'currentStock',
            'lowStockItems',
            'outOfStockItems',
            'totalValue',
            'summary',
            'transactions',
            'turnoverData',
            'menuItems'
        ))->layout('layouts.app-layout');
    }
}
