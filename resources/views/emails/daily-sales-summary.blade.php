<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales Summary</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2563eb;
            margin-bottom: 20px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .summary-item {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
        }
        .summary-item label {
            display: block;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .summary-item .value {
            font-size: 24px;
            font-weight: bold;
            color: #1e293b;
        }
        .top-items {
            margin-top: 30px;
        }
        .top-items h2 {
            color: #1e293b;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .item-list {
            list-style: none;
            padding: 0;
        }
        .item-list li {
            background-color: #f8fafc;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .item-name {
            font-weight: 600;
            color: #1e293b;
        }
        .item-stats {
            color: #64748b;
            font-size: 14px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Daily Sales Summary</h1>
        <p>Hello {{ $admin->name }},</p>
        <p>Here's your daily sales summary for <strong>{{ $date->format('F d, Y') }}</strong>:</p>

        <div class="summary-grid">
            <div class="summary-item">
                <label>Total Orders</label>
                <div class="value">{{ $totalOrders }}</div>
            </div>
            <div class="summary-item">
                <label>Total Revenue</label>
                <div class="value">${{ number_format($totalRevenue, 2) }}</div>
            </div>
            <div class="summary-item">
                <label>Completed Orders</label>
                <div class="value">{{ $completedOrders }}</div>
            </div>
            <div class="summary-item">
                <label>Cancelled Orders</label>
                <div class="value">{{ $cancelledOrders }}</div>
            </div>
            <div class="summary-item" style="grid-column: span 2;">
                <label>Average Order Value</label>
                <div class="value">${{ number_format($averageOrderValue, 2) }}</div>
            </div>
        </div>

        @if($topItems->isNotEmpty())
        <div class="top-items">
            <h2>Top 5 Best Selling Items</h2>
            <ul class="item-list">
                @foreach($topItems as $item)
                <li>
                    <span class="item-name">{{ $item['name'] }}</span>
                    <span class="item-stats">
                        {{ $item['quantity'] }} sold | ${{ number_format($item['revenue'], 2) }}
                    </span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="footer">
            <p>This is an automated report from SeaCliff Dining Management System</p>
            <p>&copy; {{ date('Y') }} SeaCliff Dining. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
