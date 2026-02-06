<?php

namespace App\Livewire;

use App\Models\Staff;
use App\Models\Tip;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;

class StaffReports extends Component
{
    public $start_date;

    public $end_date;

    public $selected_staff_id = 'all';

    public $sort_by = 'orders';

    public $sort_direction = 'desc';

    public function mount()
    {
        // Set default date range (current month)
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->format('Y-m-d');
    }

    /**
     * Get all active waiters for the staff selector
     * Optimized to select only needed columns
     */
    public function getWaiters()
    {
        return Staff::select('id', 'name')
            ->where('role', 'waiter')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get staff performance metrics
     */
    public function getStaffPerformance()
    {
        $query = Staff::select('staff.id', 'staff.name', 'staff.email')
            ->where('staff.role', 'waiter')
            ->where('staff.status', 'active');

        // Filter by specific staff member if selected
        if ($this->selected_staff_id !== 'all') {
            $query->where('staff.id', $this->selected_staff_id);
        }

        // Join orders and calculate metrics
        $staffPerformance = $query->leftJoin('orders', function ($join) {
            $join->on('staff.id', '=', 'orders.waiter_id')
                ->whereBetween('orders.created_at', [
                    $this->start_date.' 00:00:00',
                    $this->end_date.' 23:59:59',
                ])
                ->where('orders.status', '!=', 'cancelled');
        })
            ->leftJoin('tips', function ($join) {
                $join->on('staff.id', '=', 'tips.waiter_id')
                    ->whereBetween('tips.created_at', [
                        $this->start_date.' 00:00:00',
                        $this->end_date.' 23:59:59',
                    ]);
            })
            ->groupBy('staff.id', 'staff.name', 'staff.email')
            ->selectRaw('COUNT(DISTINCT orders.id) as total_orders')
            ->selectRaw('COALESCE(SUM(orders.total), 0) as total_revenue')
            ->selectRaw('CASE WHEN COUNT(DISTINCT orders.id) > 0 THEN COALESCE(SUM(orders.total), 0) / COUNT(DISTINCT orders.id) ELSE 0 END as average_order_value')
            ->selectRaw('COALESCE(SUM(tips.amount), 0) as total_tips')
            ->get();

        // Calculate additional metrics for each staff member
        $staffPerformance = $staffPerformance->map(function ($staff) {
            // Get tip breakdown by method - optimized query with only needed columns
            $tipBreakdown = Tip::select('tip_method')
                ->selectRaw('SUM(amount) as total')
                ->where('waiter_id', $staff->id)
                ->whereBetween('created_at', [
                    $this->start_date.' 00:00:00',
                    $this->end_date.' 23:59:59',
                ])
                ->groupBy('tip_method')
                ->get()
                ->pluck('total', 'tip_method')
                ->toArray();

            // Calculate average tip percentage
            $totalRevenue = (float) $staff->total_revenue;
            $totalTips = (float) $staff->total_tips;
            $averageTipPercentage = $totalRevenue > 0 ? ($totalTips / $totalRevenue) * 100 : 0;

            $staff->tip_breakdown = $tipBreakdown;
            $staff->average_tip_percentage = $averageTipPercentage;

            return $staff;
        });

        // Sort results
        $staffPerformance = $this->sortStaffPerformance($staffPerformance);

        return $staffPerformance;
    }

    /**
     * Sort staff performance data
     */
    private function sortStaffPerformance($collection)
    {
        $sortBy = $this->sort_by;
        $direction = $this->sort_direction === 'asc' ? 1 : -1;

        return $collection->sort(function ($a, $b) use ($sortBy, $direction) {
            $aValue = 0;
            $bValue = 0;

            switch ($sortBy) {
                case 'orders':
                    $aValue = $a->total_orders;
                    $bValue = $b->total_orders;
                    break;
                case 'revenue':
                    $aValue = $a->total_revenue;
                    $bValue = $b->total_revenue;
                    break;
                case 'tips':
                    $aValue = $a->total_tips;
                    $bValue = $b->total_tips;
                    break;
                default:
                    $aValue = $a->total_orders;
                    $bValue = $b->total_orders;
            }

            if ($aValue == $bValue) {
                return 0;
            }

            return ($aValue < $bValue ? -1 : 1) * $direction;
        })->values();
    }

    /**
     * Toggle sort direction or change sort column
     */
    public function sortBy($column)
    {
        if ($this->sort_by === $column) {
            // Toggle direction if same column
            $this->sort_direction = $this->sort_direction === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new column and default to descending
            $this->sort_by = $column;
            $this->sort_direction = 'desc';
        }
    }

    /**
     * Get chart data for waiter comparison
     */
    public function getChartData()
    {
        $staffPerformance = $this->getStaffPerformance();

        return [
            'labels' => $staffPerformance->pluck('name')->toArray(),
            'orders' => $staffPerformance->pluck('total_orders')->toArray(),
            'revenue' => $staffPerformance->pluck('total_revenue')->toArray(),
            'tips' => $staffPerformance->pluck('total_tips')->toArray(),
        ];
    }

    /**
     * Export report to PDF
     */
    public function exportPdf()
    {
        $data = [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'staff_performance' => $this->getStaffPerformance(),
        ];

        $pdf = Pdf::loadView('reports.staff-pdf', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'staff-performance-report-'.$this->start_date.'-to-'.$this->end_date.'.pdf');
    }

    /**
     * Export report to CSV
     */
    public function exportCsv()
    {
        $staffPerformance = $this->getStaffPerformance();

        $filename = 'staff-performance-report-'.$this->start_date.'-to-'.$this->end_date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($staffPerformance) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Waiter Name',
                'Email',
                'Total Orders',
                'Total Revenue',
                'Average Order Value',
                'Total Tips',
                'Average Tip %',
                'Cash Tips',
                'Card Tips',
                'Mobile Tips',
            ]);

            // Add data rows
            foreach ($staffPerformance as $staff) {
                fputcsv($file, [
                    $staff->name,
                    $staff->email,
                    $staff->total_orders,
                    number_format($staff->total_revenue, 2),
                    number_format($staff->average_order_value, 2),
                    number_format($staff->total_tips, 2),
                    number_format($staff->average_tip_percentage, 2).'%',
                    number_format($staff->tip_breakdown['cash'] ?? 0, 2),
                    number_format($staff->tip_breakdown['card'] ?? 0, 2),
                    number_format($staff->tip_breakdown['mobile'] ?? 0, 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $waiters = $this->getWaiters();
        $staffPerformance = $this->getStaffPerformance();
        $chartData = $this->getChartData();

        return view('livewire.staff-reports', compact(
            'waiters',
            'staffPerformance',
            'chartData'
        ))->layout('layouts.app-layout');
    }
}
