<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report</title>
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
            width: 25%;
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

        .list-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #E5E7EB;
        }

        .list-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sales Report</h1>
        <p>Comprehensive Sales Analysis</p>
    </div>

    <div class="date-range">
        <strong>Report Period:</strong> {{ date('F d, Y', strtotime($start_date)) }} - {{ date('F d, Y', strtotime($end_date)) }}
    </div>

    {{-- Summary Cards --}}
    <div class="summary-grid">
        <div class="summary-row">
            <div class="summary-card">
                <h3>Total Revenue</h3>
                <div class="value">${{ number_format($summary['total_revenue'], 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Total Orders</h3>
                <div class="value">{{ number_format($summary['total_orders']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Avg Order Value</h3>
                <div class="value">${{ number_format($summary['average_order_value'], 2) }}</div>
            </div>
            <div class="summary-card">
                <h3>Total Tax</h3>
                <div class="value">${{ number_format($summary['total_tax'], 2) }}</div>
            </div>
        </div>
    </div>

    {{-- Revenue by Category and Payment Method --}}
    <div class="two-column">
        <div class="column">
            <div class="section">
                <h2 class="section-title">Revenue by Category</h2>
                @if($revenue_by_category->count() > 0)
                    @foreach($revenue_by_category as $category)
                        <div class="list-item">
                            <span>{{ $category->name }}</span>
                            <span class="font-bold">${{ number_format($category->total_revenue, 2) }}</span>
                        </div>
                    @endforeach
                @else
                    <p style="color: #6B7280; padding: 10px 0;">No category data available</p>
                @endif
            </div>
        </div>

        <div class="column">
            <div class="section">
                <h2 class="section-title">Revenue by Payment Method</h2>
                @if($revenue_by_payment->count() > 0)
                    @foreach($revenue_by_payment as $payment)
                        <div class="list-item">
                            <span style="text-transform: capitalize;">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                            <span class="font-bold">${{ number_format($payment->total_amount, 2) }}</span>
                        </div>
                    @endforeach
                @else
                    <p style="color: #6B7280; padding: 10px 0;">No payment data available</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Daily Revenue --}}
    <div class="section">
        <h2 class="section-title">Daily Revenue Breakdown</h2>
        @if(count($daily_revenue['labels']) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($daily_revenue['labels'] as $index => $date)
                        <tr>
                            <td>{{ $date }}</td>
                            <td class="text-right font-bold">${{ number_format($daily_revenue['data'][$index], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #6B7280; padding: 10px 0;">No daily revenue data available</p>
        @endif
    </div>

    {{-- Top Selling Items --}}
    <div class="section">
        <h2 class="section-title">Top Selling Items</h2>
        @if($top_items->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Quantity Sold</th>
                        <th class="text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td class="text-right">${{ number_format($item->price, 2) }}</td>
                            <td class="text-right">{{ number_format($item->total_quantity) }}</td>
                            <td class="text-right font-bold">${{ number_format($item->total_revenue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color: #6B7280; padding: 10px 0;">No sales data available</p>
        @endif
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
