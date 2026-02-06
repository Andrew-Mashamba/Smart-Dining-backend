<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ManagerController extends Controller
{
    public function dashboard()
    {
        $activeOrders = Order::whereNotIn('status', ['completed', 'cancelled'])->count();

        $todayRevenue = Payment::whereDate('created_at', Carbon::today())
            ->where('status', 'completed')
            ->sum('amount');

        $occupiedTables = Table::where('status', 'occupied')->count();
        $totalTables = Table::count();

        $staffOnDuty = Staff::where('status', 'active')->count();

        $recentOrders = Order::with(['guest', 'table', 'waiter'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('manager.dashboard', compact(
            'activeOrders',
            'todayRevenue',
            'occupiedTables',
            'totalTables',
            'staffOnDuty',
            'recentOrders'
        ));
    }

    /**
     * Generate and download PDF receipt for an order.
     *
     * @param  int  $orderId
     * @return \Illuminate\Http\Response
     */
    public function generateReceipt($orderId)
    {
        // Load order with all necessary relationships
        $order = Order::with([
            'orderItems.menuItem',
            'table',
            'waiter',
            'payments',
            'tip',
        ])->findOrFail($orderId);

        // Generate PDF from the receipt blade template
        $pdf = Pdf::loadView('receipts.order-receipt', compact('order'));

        // Set paper size for thermal printer (80mm width)
        $pdf->setPaper([0, 0, 226.77, 841.89], 'portrait'); // 80mm x 297mm (A4 height)

        // Return PDF as download
        return $pdf->download('receipt-'.$order->order_number.'.pdf');
    }
}
