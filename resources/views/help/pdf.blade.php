<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        /* PDF-specific styling */
        @page {
            margin: 2cm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }

        h1 {
            color: #1a202c;
            font-size: 24pt;
            font-weight: bold;
            margin-top: 0;
            margin-bottom: 1em;
            page-break-after: avoid;
        }

        h2 {
            color: #2d3748;
            font-size: 18pt;
            font-weight: bold;
            margin-top: 1.5em;
            margin-bottom: 0.75em;
            padding-bottom: 0.3em;
            border-bottom: 2px solid #e2e8f0;
            page-break-after: avoid;
        }

        h3 {
            color: #4a5568;
            font-size: 14pt;
            font-weight: bold;
            margin-top: 1.25em;
            margin-bottom: 0.5em;
            page-break-after: avoid;
        }

        h4 {
            color: #718096;
            font-size: 12pt;
            font-weight: bold;
            margin-top: 1em;
            margin-bottom: 0.5em;
            page-break-after: avoid;
        }

        p {
            margin-bottom: 0.75em;
            text-align: justify;
        }

        ul, ol {
            margin-top: 0.5em;
            margin-bottom: 0.75em;
            padding-left: 1.5em;
        }

        li {
            margin-bottom: 0.3em;
        }

        strong {
            font-weight: bold;
            color: #1a202c;
        }

        em {
            font-style: italic;
        }

        code {
            background-color: #f7fafc;
            color: #2d3748;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 10pt;
        }

        pre {
            background-color: #2d3748;
            color: #f7fafc;
            padding: 1em;
            border-radius: 4px;
            overflow-x: auto;
            margin: 1em 0;
            page-break-inside: avoid;
        }

        pre code {
            background-color: transparent;
            color: #f7fafc;
            padding: 0;
        }

        hr {
            border: 0;
            border-top: 1px solid #e2e8f0;
            margin: 1.5em 0;
        }

        blockquote {
            border-left: 4px solid #4299e1;
            padding-left: 1em;
            color: #718096;
            font-style: italic;
            margin: 1em 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1em 0;
            page-break-inside: avoid;
        }

        th {
            background-color: #f7fafc;
            font-weight: bold;
            text-align: left;
            padding: 0.5em;
            border: 1px solid #cbd5e0;
        }

        td {
            padding: 0.5em;
            border: 1px solid #cbd5e0;
        }

        a {
            color: #3182ce;
            text-decoration: none;
        }

        /* Header and Footer */
        .header {
            position: fixed;
            top: -1.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            text-align: center;
            color: #718096;
            font-size: 9pt;
        }

        .footer {
            position: fixed;
            bottom: -1.5cm;
            left: 0;
            right: 0;
            height: 1cm;
            text-align: center;
            color: #718096;
            font-size: 9pt;
            border-top: 1px solid #e2e8f0;
            padding-top: 0.3cm;
        }

        .page-number:after {
            content: "Page " counter(page);
        }

        /* Content container */
        .content {
            margin-top: 0;
        }

        /* Prevent orphans and widows */
        p, li {
            orphans: 3;
            widows: 3;
        }

        /* Title page styling */
        .title-page {
            text-align: center;
            padding: 5cm 0;
            page-break-after: always;
        }

        .title-page h1 {
            font-size: 32pt;
            margin-bottom: 1em;
            border: none;
        }

        .title-page .subtitle {
            font-size: 14pt;
            color: #718096;
            margin-bottom: 2em;
        }

        .title-page .date {
            font-size: 11pt;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        SeaCliff POS Documentation
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="page-number"></div>
    </div>

    <!-- Title Page -->
    <div class="title-page">
        <h1>{{ $title }}</h1>
        <div class="subtitle">SeaCliff POS System</div>
        <div class="date">Generated: {{ date('F d, Y') }}</div>
    </div>

    <!-- Content -->
    <div class="content">
        {!! $content !!}
    </div>

    <!-- Copyright Footer on Last Page -->
    <div style="margin-top: 3em; padding-top: 1em; border-top: 2px solid #e2e8f0;">
        <p style="text-align: center; color: #a0aec0; font-size: 9pt;">
            © {{ date('Y') }} SeaCliff POS. All rights reserved.<br>
            This documentation is confidential and proprietary.
        </p>
    </div>
</body>
</html>
