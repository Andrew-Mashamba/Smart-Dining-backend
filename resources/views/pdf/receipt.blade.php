<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #ffffff;
            padding: 20px;
        }

        .receipt-container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            padding: 30px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1a1a1a;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #1a1a1a;
        }

        .header p {
            font-size: 11px;
            color: #4b5563;
        }

        .order-info {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .order-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .order-info-row:last-child {
            margin-bottom: 0;
        }

        .label {
            font-weight: bold;
            color: #1a1a1a;
        }

        .value {
            color: #4b5563;
        }

        .items-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .items-table thead {
            background-color: #1a1a1a;
            color: #ffffff;
        }

        .items-table th {
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
        }

        .items-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .summary {
            margin-top: 25px;
            padding: 20px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-row.total {
            padding-top: 10px;
            border-top: 2px solid #1a1a1a;
            margin-top: 10px;
        }

        .summary-row .label {
            font-size: 13px;
        }

        .summary-row.total .label,
        .summary-row.total .value {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #e5e7eb;
            color: #1a1a1a;
        }

        .status-preparing {
            background-color: #9ca3af;
            color: #ffffff;
        }

        .status-ready {
            background-color: #4b5563;
            color: #ffffff;
        }

        .status-delivered {
            background-color: #374151;
            color: #ffffff;
        }

        .status-paid {
            background-color: #1a1a1a;
            color: #ffffff;
        }

        .status-cancelled {
            background-color: #d1d5db;
            color: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header with Logo -->
        <div class="header">
            <h1>{{ \App\Models\Setting::get('business_name', 'SeaCliff POS') }}</h1>
            <p>Restaurant Management System</p>
            <p style="margin-top: 5px; font-size: 10px;">{{ \App\Models\Setting::get('business_address', '123 Ocean Drive, Coastal City') }} | Phone: {{ \App\Models\Setting::get('business_phone', '(555) 123-4567') }}</p>
            <p style="font-size: 10px;">Email: {{ \App\Models\Setting::get('business_email', 'info@seacliffpos.com') }}</p>
            <p style="margin-top: 10px; font-weight: bold;">OFFICIAL RECEIPT</p>
        </div>

        <!-- Order Information -->
        <div class="order-info">
            <div class="order-info-row">
                <span class="label">Order Number:</span>
                <span class="value">{{ $order->order_number }}</span>
            </div>
            <div class="order-info-row">
                <span class="label">Date:</span>
                <span class="value">{{ $order->created_at->format('F d, Y H:i') }}</span>
            </div>
            <div class="order-info-row">
                <span class="label">Table:</span>
                <span class="value">{{ $order->table ? $order->table->name : 'N/A' }}</span>
            </div>
            <div class="order-info-row">
                <span class="label">Waiter:</span>
                <span class="value">{{ $order->waiter ? $order->waiter->name : 'N/A' }}</span>
            </div>
            <div class="order-info-row">
                <span class="label">Status:</span>
                <span class="value">
                    <span class="status-badge status-{{ $order->status }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </span>
            </div>
            @if($order->guest)
            <div class="order-info-row">
                <span class="label">Guest:</span>
                <span class="value">{{ $order->guest->name }}</span>
            </div>
            @endif
        </div>

        <!-- Order Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                <tr>
                    <td>
                        {{ $item->menuItem ? $item->menuItem->name : 'N/A' }}
                        @if($item->special_instructions)
                        <br><small style="color: #6b7280; font-size: 10px;">{{ $item->special_instructions }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Order Summary -->
        <div class="summary">
            <div class="summary-row">
                <span class="label">Subtotal:</span>
                <span class="value">${{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Tax ({{ \App\Models\Setting::get('tax_rate', 18) }}%):</span>
                <span class="value">${{ number_format($order->tax, 2) }}</span>
            </div>
            <div class="summary-row total">
                <span class="label">Total:</span>
                <span class="value">${{ number_format($order->total, 2) }}</span>
            </div>

            @php
                $totalPaid = $order->payments()->where('status', 'completed')->sum('amount');
            @endphp

            @if($totalPaid > 0)
            <div class="summary-row">
                <span class="label">Paid:</span>
                <span class="value">${{ number_format($totalPaid, 2) }}</span>
            </div>

            @if($totalPaid < $order->total)
            <div class="summary-row">
                <span class="label">Balance Due:</span>
                <span class="value">${{ number_format($order->total - $totalPaid, 2) }}</span>
            </div>
            @endif
            @endif

            @if($order->tip)
            <div class="summary-row">
                <span class="label">Tip:</span>
                <span class="value">${{ number_format($order->tip->amount, 2) }}</span>
            </div>
            @endif
        </div>

        <!-- Payment Details -->
        @if($order->payments->count() > 0)
        <div style="margin-top: 20px;">
            <h3 style="font-size: 14px; margin-bottom: 10px; color: #1a1a1a;">Payment Details</h3>
            @foreach($order->payments as $payment)
            <div class="order-info-row" style="margin-bottom: 5px;">
                <span class="value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}:</span>
                <span class="value">${{ number_format($payment->amount, 2) }} - {{ $payment->created_at->format('M d, H:i') }}</span>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Special Instructions -->
        @if($order->special_instructions)
        <div style="margin-top: 20px; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb;">
            <span class="label">Order Notes:</span><br>
            <span class="value">{{ $order->special_instructions }}</span>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Thank you for dining with us!</p>
            <p>Sea Cliff Smart Dining System</p>
            <p>Generated on {{ now()->format('F d, Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
