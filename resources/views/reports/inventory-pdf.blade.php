<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.5;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #111827;
            padding-bottom: 15px;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            color: #6B7280;
            font-size: 11px;
        }

        .date-range {
            text-align: center;
            margin-bottom: 20px;
            font-size: 11px;
            color: #6B7280;
        }

        .summary-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .summary-row {
            display: table-row;
        }

        .summary-card {
            display: table-cell;
            width: 20%;
            padding: 15px;
            border: 1px solid #E5E7EB;
            background-color: #F9FAFB;
            vertical-align: top;
        }

        .summary-card h3 {
            font-size: 10px;
            color: #6B7280;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .summary-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #111827;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background-color: #F9FAFB;
            padding: 10px;
            text-align: left;
            font-size: 10px;
            font-weight: 600;
            color: #6B7280;
            border: 1px solid #E5E7EB;
            text-transform: uppercase;
        }

        table td {
            padding: 8px 10px;
            border: 1px solid #E5E7EB;
            font-size: 11px;
            color: #111827;
        }

        table tr:nth-child(even) {
            background-color: #F9FAFB;
        }

        .low-stock-row {
            background-color: #F3F4F6 !important;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 10px;
        }

        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .column {
            display: table-cell;
            width: 49%;
            vertical-align: top;
        }

        .column:first-child {
            padding-right: 10px;
        }

        .column:last-child {
            padding-left: 10px;
        }

        .alert-item {
            padding: 10px;
            border: 1px solid #E5E7EB;
            background-color: #F3F4F6;
            margin-bottom: 8px;
        }

        .alert-item strong {
            color: #111827;
            font-size: 11px;
        }

        .alert-item .quantity {
            float: right;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 10px;
            text-transform: uppercase;
        }

        .badge-restock {
            background-color: #F3F4F6;
            color: #111827;
        }

        .badge-sale {
            background-color: #E5E7EB;
            color: #111827;
        }

        .badge-adjustment {
            background-color: #D1D5DB;
            color: #111827;
        }

        .badge-waste {
            background-color: #9CA3AF;
            color: #FFFFFF;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Inventory Report</h1>
        <p>Stock Levels, Transaction History, and Alerts</p>
    </div>

    <div class="date-range">
        <strong>Report Period:</strong> {{ date('F d, Y', strtotime($start_date)) }} - {{ date('F d, Y', strtotime($end_date)) }}
    </div>

    {{-- Summary Cards --}}
    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-card">
                <h3>Total Value</h3>
                <div class="value">${{ number_format($total_value, 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Restocks</h3>
                <div class="value">{{ number_format($summary['total_restocks']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Sales</h3>
                <div class="value">{{ number_format($summary['total_sales']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Adjustments</h3>
                <div class="value">{{ number_format($summary['total_adjustments']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Waste</h3>
                <div class="value">{{ number_format($summary['total_waste']) }}</div>
            </div>
        </div>
    </div>

    {{-- Low Stock and Out of Stock Alerts --}}
    <div class="two-column">
        <div class="column">
            <div class="section">
                <h2 class="section-title">Low Stock Alerts</h2>
                @if($low_stock->count() > 0)
                    @foreach($low_stock as $item)
                        <div class="alert-item">
                            <strong>{{ $item->name }}</strong>
                            <span class="quantity">{{ $item->stock_quantity }} {{ $item->unit }}</span>
                            <br>
                            <small style="color: #6B7280;">Threshold: {{ $item->low_stock_threshold }} {{ $item->unit }}</small>
                        </div>
                    @endforeach
                @else
                    <p style="color: #6B7280; padding: 10px 0;">No low stock items</p>
                @endif
            </div>
        </div>

        <div class="column">
            <div class="section">
                <h2 class="section-title">Out of Stock</h2>
                @if($out_of_stock->count() > 0)
                    @foreach($out_of_stock as $item)
                        <div class="alert-item">
                            <strong>{{ $item->name }}</strong>
                            <span class="quantity" style="color: #DC2626;">OUT OF STOCK</span>
                            <br>
                            <small style="color: #6B7280;">Unit: {{ $item->unit }}</small>
                        </div>
                    @endforeach
                @else
                    <p style="color: #6B7280; padding: 10px 0;">All items in stock</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Current Stock Summary --}}
    <div class="section">
        <h2 class="section-title">Current Stock Summary</h2>
        @if($current_stock->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th class="text-right">Stock Qty</th>
                        <th>Unit</th>
                        <th class="text-right">Threshold</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($current_stock as $item)
                        <tr class="{{ $item->stock_quantity < $item->low_stock_threshold ? 'low-stock-row' : '' }}">
                            <td>{{ $item->name }}</td>
                            <td class="text-right">{{ $item->stock_quantity }}</td>
                            <td>{{ $item->unit }}</td>
                            <td class="text-right">{{ $item->low_stock_threshold }}</td>
                            <td class="text-right">${{ number_format($item->price, 2) }}</td>
                            <td class="text-right font-bold">${{ number_format($item->stock_quantity * $item->price, 2) }}</td>
                            <td>
                                @if($item->stock_quantity == 0)
                                    Out of Stock
                                @elseif($item->stock_quantity < $item->low_stock_threshold)
                                    Low Stock
                                @else
                                    In Stock
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #6B7280; padding: 10px 0;">No inventory items found</p>
        @endif
    </div>

    {{-- Transaction History --}}
    <div class="section">
        <h2 class="section-title">Recent Transaction History</h2>
        @if($transactions->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Menu Item</th>
                        <th>Type</th>
                        <th class="text-right">Quantity</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions->take(50) as $transaction)
                        <tr>
                            <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $transaction->menuItem->name ?? 'Unknown' }}</td>
                            <td>
                                <span class="badge badge-{{ $transaction->transaction_type }}">
                                    {{ ucfirst($transaction->transaction_type) }}
                                </span>
                            </td>
                            <td class="text-right">{{ $transaction->quantity }} {{ $transaction->unit }}</td>
                            <td>{{ $transaction->createdBy->name ?? 'Unknown' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($transactions->count() > 50)
                <p style="color: #6B7280; text-align: center; font-size: 10px;">Showing first 50 transactions only</p>
            @endif
        @else
            <p style="color: #6B7280; padding: 10px 0;">No transactions found for this period</p>
        @endif
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
