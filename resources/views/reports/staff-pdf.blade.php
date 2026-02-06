<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Performance Report</title>
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
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            color: #6B7280;
            border: 1px solid #E5E7EB;
            text-transform: uppercase;
        }

        table td {
            padding: 6px;
            border: 1px solid #E5E7EB;
            font-size: 10px;
            color: #111827;
        }

        table tr:nth-child(even) {
            background-color: #F9FAFB;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
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

        .staff-name {
            font-weight: bold;
            color: #111827;
        }

        .staff-email {
            font-size: 9px;
            color: #6B7280;
            display: block;
            margin-top: 2px;
        }

        .tip-breakdown {
            font-size: 9px;
            line-height: 1.4;
        }

        .tip-breakdown .label {
            color: #6B7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Staff Performance Report</h1>
        <p>Waiter Performance Analytics and Metrics</p>
    </div>

    <div class="date-range">
        <strong>Report Period:</strong> {{ date('F d, Y', strtotime($start_date)) }} - {{ date('F d, Y', strtotime($end_date)) }}
    </div>

    {{-- Staff Performance Table --}}
    <div class="section">
        <h2 class="section-title">Performance Metrics by Waiter</h2>
        @if($staff_performance->count() > 0)
            <table>
                <thead>
                    <tr>
                        <th>Waiter</th>
                        <th class="text-center">Orders</th>
                        <th class="text-right">Revenue</th>
                        <th class="text-right">Avg Order</th>
                        <th class="text-right">Tips</th>
                        <th class="text-right">Avg Tip %</th>
                        <th>Tip Breakdown</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff_performance as $staff)
                        <tr>
                            <td>
                                <span class="staff-name">{{ $staff->name }}</span>
                                <span class="staff-email">{{ $staff->email }}</span>
                            </td>
                            <td class="text-center">{{ number_format($staff->total_orders) }}</td>
                            <td class="text-right font-bold">${{ number_format($staff->total_revenue, 2) }}</td>
                            <td class="text-right">${{ number_format($staff->average_order_value, 2) }}</td>
                            <td class="text-right font-bold">${{ number_format($staff->total_tips, 2) }}</td>
                            <td class="text-right">{{ number_format($staff->average_tip_percentage, 1) }}%</td>
                            <td>
                                <div class="tip-breakdown">
                                    @if(isset($staff->tip_breakdown['cash']) && $staff->tip_breakdown['cash'] > 0)
                                        <div>
                                            <span class="label">Cash:</span> ${{ number_format($staff->tip_breakdown['cash'], 2) }}
                                        </div>
                                    @endif
                                    @if(isset($staff->tip_breakdown['card']) && $staff->tip_breakdown['card'] > 0)
                                        <div>
                                            <span class="label">Card:</span> ${{ number_format($staff->tip_breakdown['card'], 2) }}
                                        </div>
                                    @endif
                                    @if(isset($staff->tip_breakdown['mobile']) && $staff->tip_breakdown['mobile'] > 0)
                                        <div>
                                            <span class="label">Mobile:</span> ${{ number_format($staff->tip_breakdown['mobile'], 2) }}
                                        </div>
                                    @endif
                                    @if(empty($staff->tip_breakdown))
                                        <span style="color: #9CA3AF;">No tips</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Summary Statistics --}}
            <div style="margin-top: 30px; padding: 15px; background-color: #F9FAFB; border: 1px solid #E5E7EB;">
                <h3 style="font-size: 12px; margin-bottom: 10px; color: #111827;">Summary Statistics</h3>
                <table style="border: none; margin: 0;">
                    <tr style="background-color: transparent;">
                        <td style="border: none; padding: 5px 0; font-weight: bold; color: #6B7280;">Total Orders Served:</td>
                        <td style="border: none; padding: 5px 0; text-align: right; font-weight: bold;">
                            {{ number_format($staff_performance->sum('total_orders')) }}
                        </td>
                        <td style="border: none; padding: 5px 0; width: 20px;"></td>
                        <td style="border: none; padding: 5px 0; font-weight: bold; color: #6B7280;">Total Revenue Generated:</td>
                        <td style="border: none; padding: 5px 0; text-align: right; font-weight: bold;">
                            ${{ number_format($staff_performance->sum('total_revenue'), 2) }}
                        </td>
                    </tr>
                    <tr style="background-color: transparent;">
                        <td style="border: none; padding: 5px 0; font-weight: bold; color: #6B7280;">Total Tips Earned:</td>
                        <td style="border: none; padding: 5px 0; text-align: right; font-weight: bold;">
                            ${{ number_format($staff_performance->sum('total_tips'), 2) }}
                        </td>
                        <td style="border: none; padding: 5px 0; width: 20px;"></td>
                        <td style="border: none; padding: 5px 0; font-weight: bold; color: #6B7280;">Average Order Value:</td>
                        <td style="border: none; padding: 5px 0; text-align: right; font-weight: bold;">
                            ${{ number_format($staff_performance->avg('average_order_value'), 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        @else
            <p style="color: #6B7280; padding: 20px 0; text-align: center;">No staff performance data available for this period</p>
        @endif
    </div>

    <div class="footer">
        <p>Generated on {{ now()->format('F d, Y \a\t h:i A') }}</p>
        <p>This is a computer-generated document. No signature is required.</p>
    </div>
</body>
</html>
