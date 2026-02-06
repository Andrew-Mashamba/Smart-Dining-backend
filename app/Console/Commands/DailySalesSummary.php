<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DailySalesSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:daily-sales-summary {--date=yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily sales summary email to management';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') === 'yesterday'
            ? Carbon::yesterday()
            : Carbon::parse($this->option('date'));

        $this->info("Generating sales summary for {$date->toDateString()}...");

        // Get daily sales data
        $orders = Order::whereDate('created_at', $date)
            ->with('items', 'user')
            ->get();

        $totalOrders = $orders->count();
        $totalRevenue = $orders->sum('total_amount');
        $completedOrders = $orders->where('status', 'completed')->count();
        $cancelledOrders = $orders->where('status', 'cancelled')->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Get top selling items
        $topItems = $orders->flatMap(function ($order) {
            return $order->items;
        })->groupBy('menu_item_id')->map(function ($items) {
            return [
                'name' => $items->first()->menuItem->name ?? 'Unknown',
                'quantity' => $items->sum('quantity'),
                'revenue' => $items->sum(function ($item) {
                    return $item->quantity * $item->price;
                }),
            ];
        })->sortByDesc('quantity')->take(5);

        // Get admin users who should receive the report
        $admins = User::where('role', 'admin')
            ->orWhere('role', 'manager')
            ->get();

        if ($admins->isEmpty()) {
            $this->warn('No admin users found to send the report to.');

            return 0;
        }

        // Send email to each admin
        foreach ($admins as $admin) {
            try {
                Mail::send('emails.daily-sales-summary', [
                    'admin' => $admin,
                    'date' => $date,
                    'totalOrders' => $totalOrders,
                    'totalRevenue' => $totalRevenue,
                    'completedOrders' => $completedOrders,
                    'cancelledOrders' => $cancelledOrders,
                    'averageOrderValue' => $averageOrderValue,
                    'topItems' => $topItems,
                ], function ($message) use ($admin, $date) {
                    $message->to($admin->email)
                        ->subject('Daily Sales Summary - '.$date->toDateString());
                });

                $this->info("Sent report to {$admin->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send report to {$admin->email}: {$e->getMessage()}");
            }
        }

        $this->info('Daily sales summary completed!');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Orders', $totalOrders],
                ['Total Revenue', '$'.number_format($totalRevenue, 2)],
                ['Completed Orders', $completedOrders],
                ['Cancelled Orders', $cancelledOrders],
                ['Average Order Value', '$'.number_format($averageOrderValue, 2)],
            ]
        );

        return 0;
    }
}
