<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        /* Thermal printer-friendly monochrome styling */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            width: 80mm; /* Standard thermal printer width */
            margin: 0 auto;
            padding: 10px;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }

        .business-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .business-info {
            font-size: 10px;
            line-height: 1.3;
        }

        .receipt-title {
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }

        .order-info {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #666;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .info-label {
            font-weight: bold;
        }

        .items-section {
            margin-bottom: 15px;
        }

        .items-header {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .items-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .item-row {
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #999;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .item-details {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .item-qty-price {
            flex: 1;
        }

        .item-subtotal {
            text-align: right;
            font-weight: bold;
        }

        .special-instructions {
            font-size: 10px;
            font-style: italic;
            margin-top: 2px;
            padding-left: 10px;
            color: #333;
        }

        .totals-section {
            margin-bottom: 15px;
            padding: 10px 0;
            border-top: 1px solid #000;
            border-bottom: 2px solid #000;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #000;
        }

        .total-label {
            text-transform: uppercase;
        }

        .total-amount {
            font-weight: bold;
        }

        .payment-section {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #666;
        }

        .payment-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #000;
        }

        .thank-you {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .tagline {
            font-size: 11px;
            font-style: italic;
            margin-bottom: 5px;
        }

        .footer-info {
            font-size: 9px;
            margin-top: 8px;
            color: #333;
        }

        /* Print-specific styles */
        @media print {
            body {
                width: 80mm;
            }
        }
    </style>
</head>
<body>
    {{-- Receipt Header with Business Information --}}
    <div class="receipt-header">
        <div class="business-name">{{ \App\Models\Setting::get('business_name', config('app.name', 'SeaCliff Dining')) }}</div>
        <div class="business-info">
            {{ \App\Models\Setting::get('business_address', '123 Ocean View Drive, Cape Town, South Africa 8001') }}<br>
            Tel: {{ \App\Models\Setting::get('business_phone', '+27 21 123 4567') }}<br>
            Email: {{ \App\Models\Setting::get('business_email', 'info@seacliff-dining.co.za') }}
        </div>
    </div>

    <div class="receipt-title">Order Receipt</div>

    {{-- Order Information --}}
    <div class="order-info">
        <div class="info-row">
            <span class="info-label">Order #:</span>
            <span>{{ $order->order_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span>{{ $order->created_at->format('d M Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time:</span>
            <span>{{ $order->created_at->format('H:i:s') }}</span>
        </div>
        @if($order->table)
        <div class="info-row">
            <span class="info-label">Table:</span>
            <span>{{ $order->table->name }}</span>
        </div>
        @endif
        @if($order->waiter)
        <div class="info-row">
            <span class="info-label">Waiter:</span>
            <span>{{ $order->waiter->name ?? 'N/A' }}</span>
        </div>
        @endif
    </div>

    {{-- Items Section --}}
    <div class="items-section">
        <div class="items-header">ITEMS ORDERED</div>
        <div class="items-table">
            @foreach($order->orderItems as $item)
            <div class="item-row">
                <div class="item-name">{{ $item->menuItem->name ?? 'Unknown Item' }}</div>
                <div class="item-details">
                    <div class="item-qty-price">
                        {{ $item->quantity }} x R{{ number_format($item->unit_price, 2) }}
                    </div>
                    <div class="item-subtotal">
                        R{{ number_format($item->subtotal, 2) }}
                    </div>
                </div>
                @if($item->special_instructions)
                <div class="special-instructions">
                    Note: {{ $item->special_instructions }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- Totals Section --}}
    <div class="totals-section">
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-amount">R{{ number_format($order->subtotal, 2) }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Tax ({{ \App\Models\Setting::get('tax_rate', 18) }}% VAT):</span>
            <span class="total-amount">R{{ number_format($order->tax, 2) }}</span>
        </div>
        @if($order->tip)
        <div class="total-row">
            <span class="total-label">Tip:</span>
            <span class="total-amount">R{{ number_format($order->tip->amount, 2) }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span class="total-label">TOTAL:</span>
            <span class="total-amount">R{{ number_format($order->total + ($order->tip->amount ?? 0), 2) }}</span>
        </div>
    </div>

    {{-- Payment Information --}}
    <div class="payment-section">
        <div class="payment-title">Payment Details</div>
        @if($order->payments->isNotEmpty())
            @php
                $payment = $order->payments->first();
            @endphp
            <div class="payment-row">
                <span class="info-label">Method:</span>
                <span>{{ ucfirst($payment->payment_method) }}</span>
            </div>
            <div class="payment-row">
                <span class="info-label">Amount Paid:</span>
                <span>R{{ number_format($payment->amount, 2) }}</span>
            </div>
            @if($payment->payment_method === 'cash')
                @php
                    $totalDue = $order->total + ($order->tip->amount ?? 0);
                    $change = $payment->amount - $totalDue;
                @endphp
                @if($change > 0)
                <div class="payment-row">
                    <span class="info-label">Change:</span>
                    <span>R{{ number_format($change, 2) }}</span>
                </div>
                @endif
            @endif
            @if($payment->status)
            <div class="payment-row">
                <span class="info-label">Status:</span>
                <span>{{ ucfirst($payment->status) }}</span>
            </div>
            @endif
        @else
            <div class="payment-row">
                <span>No payment recorded</span>
            </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="thank-you">Thank You!</div>
        <div class="tagline">Where Ocean Meets Flavor</div>
        <div class="footer-info">
            Visit us again soon!<br>
            www.seacliff-dining.co.za<br>
            Follow us @seacliff_dining
        </div>
    </div>
</body>
</html>
