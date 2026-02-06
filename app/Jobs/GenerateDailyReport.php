<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Tip;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateDailyReport implements ShouldQueue
{
    use Queueable;

    public string $date;

    /**
     * Create a new job instance.
     */
    public function __construct(?string $date = null)
    {
        $this->date = $date ?? Carbon::yesterday()->format('Y-m-d');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startOfDay = Carbon::parse($this->date)->startOfDay();
        $endOfDay = Carbon::parse($this->date)->endOfDay();

        // Generate comprehensive daily report
        $report = [
            'date' => $this->date,
            'orders' => $this->getOrderStats($startOfDay, $endOfDay),
            'revenue' => $this->getRevenueStats($startOfDay, $endOfDay),
            'tips' => $this->getTipStats($startOfDay, $endOfDay),
            'popular_items' => $this->getPopularItems($startOfDay, $endOfDay),
        ];

        \Log::info('Daily report generated', $report);

        // In production:
        // - Store report in database
        // - Send email to manager
        // - Upload to cloud storage
        // - Trigger analytics dashboard update
    }

    protected function getOrderStats($start, $end): array
    {
        $orders = Order::whereBetween('created_at', [$start, $end])->get();

        return [
            'total_orders' => $orders->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'by_source' => $orders->groupBy('order_source')->map->count(),
        ];
    }

    protected function getRevenueStats($start, $end): array
    {
        $payments = Payment::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->get();

        return [
            'total_revenue' => $payments->sum('amount'),
            'payment_count' => $payments->count(),
            'by_method' => $payments->groupBy('payment_method')
                ->map(fn ($p) => ['count' => $p->count(), 'total' => $p->sum('amount')]),
        ];
    }

    protected function getTipStats($start, $end): array
    {
        $tips = Tip::whereBetween('created_at', [$start, $end])->get();

        return [
            'total_tips' => $tips->sum('amount'),
            'tip_count' => $tips->count(),
            'average_tip' => $tips->avg('amount'),
        ];
    }

    protected function getPopularItems($start, $end): array
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->with('items.menuItem')
            ->get()
            ->flatMap->items
            ->groupBy('menu_item_id')
            ->map(function ($items) {
                return [
                    'name' => $items->first()->menuItem->name,
                    'quantity' => $items->sum('quantity'),
                    'revenue' => $items->sum('subtotal'),
                ];
            })
            ->sortByDesc('quantity')
            ->take(10)
            ->values()
            ->toArray();
    }
}
