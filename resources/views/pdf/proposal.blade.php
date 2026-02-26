<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Proposal - {{ $proposal->title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.5;
            padding: 40px;
        }
        .letterhead {
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .letterhead h1 {
            font-size: 22pt;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .letterhead .contact {
            font-size: 9pt;
            color: #4b5563;
        }
        .meta {
            margin-bottom: 28px;
        }
        .meta table { width: 100%; }
        .meta td { padding: 4px 12px 4px 0; vertical-align: top; }
        .meta .label { font-weight: bold; color: #374151; }
        .proposal-title {
            font-size: 16pt;
            font-weight: bold;
            margin: 24px 0 16px;
            color: #1a1a1a;
        }
        .summary {
            background: #f9fafb;
            border-left: 4px solid #1a1a1a;
            padding: 14px 18px;
            margin: 20px 0;
        }
        .body-content {
            margin: 20px 0;
            white-space: pre-wrap;
        }
        .amount-block {
            margin-top: 32px;
            padding: 20px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
        }
        .amount-block .total {
            font-size: 18pt;
            font-weight: bold;
        }
        .validity {
            margin-top: 24px;
            font-size: 10pt;
            color: #6b7280;
        }
        .footer-note {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 9pt;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ \App\Models\Setting::get('business_name', config('app.name', 'Smart Dining')) }}</h1>
        <p class="contact">
            {{ \App\Models\Setting::get('business_address', '') }}
            @if(\App\Models\Setting::get('business_phone'))
                &nbsp;| {{ \App\Models\Setting::get('business_phone') }}
            @endif
            @if(\App\Models\Setting::get('business_email'))
                &nbsp;| {{ \App\Models\Setting::get('business_email') }}
            @endif
        </p>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Reference</td>
                <td>{{ $proposal->reference }}</td>
            </tr>
            <tr>
                <td class="label">Date</td>
                <td>{{ $proposal->created_at->format('d M Y') }}</td>
            </tr>
            <tr>
                <td class="label">Client</td>
                <td>{{ $proposal->client_name }}{{ $proposal->client_company ? ' — ' . $proposal->client_company : '' }}</td>
            </tr>
            @if($proposal->client_email)
            <tr>
                <td class="label">Email</td>
                <td>{{ $proposal->client_email }}</td>
            </tr>
            @endif
        </table>
    </div>

    <h2 class="proposal-title">{{ $proposal->title }}</h2>

    @if($proposal->summary)
    <div class="summary">{{ $proposal->summary }}</div>
    @endif

    @if($proposal->body)
    <div class="body-content">{{ $proposal->body }}</div>
    @endif

    <div class="amount-block">
        <span class="label">Total amount</span>
        <div class="total">{{ $proposal->currency }} {{ number_format($proposal->amount, 2) }}</div>
    </div>

    @if($proposal->valid_until)
    <p class="validity">This proposal is valid until {{ $proposal->valid_until->format('d M Y') }}.</p>
    @endif

    <div class="footer-note">
        Thank you for your business. If you have any questions, please contact us using the details above.
    </div>
</body>
</html>
