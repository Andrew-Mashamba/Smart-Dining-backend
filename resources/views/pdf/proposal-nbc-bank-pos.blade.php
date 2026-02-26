<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Proposal – POS Devices for International Card Processing</title>
    <style>
        /* ZIMA brand: blue-900 #1e3a8a, deep yellow #ca8a04 */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            color: #1e293b;
            line-height: 1.55;
            padding: 0;
            background: #fff;
        }
        .page { padding: 36px 44px 44px; }

        .letterhead {
            background: #1e3a8a;
            color: #fff;
            padding: 20px 24px 18px;
            margin: -36px -44px 28px -44px;
        }
        .letterhead h1 { font-size: 24pt; font-weight: bold; letter-spacing: -0.02em; margin-bottom: 4px; }
        .letterhead .tagline { font-size: 9.5pt; color: #ca8a04; font-style: italic; margin-bottom: 10px; }
        .letterhead .contact { font-size: 9pt; color: #e2e8f0; line-height: 1.5; }
        .letterhead .contact a { color: #ca8a04; text-decoration: none; }
        .letterhead-accent { height: 4px; background: #ca8a04; margin-top: 14px; }

        .meta-row { margin-bottom: 22px; font-size: 9.5pt; color: #1e3a8a; font-weight: 600; }
        .to-block {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-left: 4px solid #ca8a04;
            padding: 14px 18px;
            margin-bottom: 22px;
        }
        .to-block .label { font-size: 8pt; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
        .to-block .name { font-size: 12pt; font-weight: bold; color: #1e3a8a; }
        .to-block .addr { font-size: 9.5pt; color: #475569; margin-top: 2px; }

        .subject {
            font-size: 12.5pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 20px 0 16px;
            padding-left: 14px;
            border-left: 4px solid #ca8a04;
            line-height: 1.35;
        }

        .re-merchant {
            margin: 18px 0 22px;
            padding: 14px 18px;
            border-left: 4px solid #ca8a04;
            background: #fefce8;
        }
        .re-merchant .logo { height: 52px; width: auto; display: inline-block; margin-right: 14px; vertical-align: middle; }
        .re-merchant .text { vertical-align: middle; display: inline-block; font-size: 10.5pt; color: #1e293b; }
        h2 { font-size: 11pt; font-weight: bold; color: #1e3a8a; margin: 26px 0 10px; padding-bottom: 6px; border-bottom: 2px solid #ca8a04; }
        h2:first-of-type { margin-top: 20px; }
        p { margin-bottom: 10px; }
        .summary { background: #eff6ff; border-left: 4px solid #1e3a8a; padding: 16px 20px; margin: 16px 0; }
        .summary strong { color: #1e3a8a; }
        ul { margin: 8px 0 14px 22px; }
        li { margin-bottom: 6px; }
        li strong { color: #1e3a8a; }
        .requirements { border: 1px solid #bfdbfe; border-left: 4px solid #ca8a04; padding: 16px 20px; margin: 16px 0; background: #eff6ff; }
        .requirements li { margin-bottom: 8px; }
        .closing { margin-top: 26px; padding: 14px 0; }
        .signature { margin-top: 28px; padding-top: 20px; border-top: 2px solid #1e3a8a; }
        .signature .name { font-weight: bold; color: #1e3a8a; font-size: 11pt; }
        .signature .title { font-size: 10pt; color: #475569; margin-top: 2px; }
        .footer-note { margin-top: 32px; padding: 14px 0; border-top: 1px solid #bfdbfe; font-size: 8.5pt; color: #64748b; text-align: center; }

        /* Page breaks: avoid cutting content across pages */
        .title-page { page-break-after: always; padding: 80px 44px 60px; text-align: center; }
        .title-page .brand { background: #1e3a8a; color: #fff; padding: 16px 24px; margin: -80px -44px 40px -44px; }
        .title-page .brand h1 { font-size: 22pt; font-weight: bold; }
        .title-page .brand .tagline { font-size: 9pt; color: #ca8a04; font-style: italic; margin-top: 4px; }
        .title-page .doc-title { font-size: 16pt; font-weight: bold; color: #1e3a8a; line-height: 1.4; margin: 24px 0 12px; max-width: 420px; margin-left: auto; margin-right: auto; }
        .title-page .doc-subtitle { font-size: 11pt; color: #475569; margin-bottom: 32px; }
        .title-page .doc-meta { font-size: 10pt; color: #64748b; }
        .title-page .accent { height: 4px; background: #ca8a04; width: 120px; margin: 32px auto 0; }

        .contents-page { page-break-after: always; padding: 48px 44px 60px; }
        .contents-page h2 { font-size: 14pt; color: #1e3a8a; border: none; margin-bottom: 24px; padding-bottom: 0; }
        .contents-page ul { list-style: none; margin: 0; padding: 0; }
        .contents-page li { margin-bottom: 10px; padding: 6px 0 6px 12px; border-left: 3px solid #ca8a04; page-break-inside: avoid; }
        .contents-page li a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
        .contents-page .toc-num { color: #64748b; font-weight: bold; margin-right: 8px; }

        .section { page-break-inside: avoid; }
        p, .summary, .requirements, .to-block, .re-merchant, .closing, .signature, .footer-note { page-break-inside: avoid; }
        h2 { page-break-after: avoid; }
        li { page-break-inside: avoid; }
    </style>
</head>
<body>

<!-- Title page -->
<div class="title-page">
    <div class="brand">
        <h1>ZIMA Solutions</h1>
        <p class="tagline">Digital transformation &amp; payment gateway | Tanzania</p>
    </div>
    <div class="doc-title">Request for Proposal: Supply of POS Devices Capable of Processing VISA, Mastercard and Other International Cards</div>
    <p class="doc-subtitle">Re: Cape Classique Restaurant &amp; Wine Bar<br />Sea Cliff Court Hotel &amp; Luxury Apartments, Masaki, Dar es Salaam</p>
    <p class="doc-meta">To: NBC Bank (National Bank of Commerce)<br />Merchant Services / Payment Aggregator &amp; Settlement</p>
    <p class="doc-meta" style="margin-top: 16px;">{{ now()->format('d F Y') }}</p>
    <div class="accent"></div>
</div>

<!-- Contents page -->
<div class="contents-page">
    <h2>Contents</h2>
    <ul>
        <li><span class="toc-num">1</span> <a href="#section-1">Stakeholders</a></li>
        <li><span class="toc-num">2</span> <a href="#section-2">About the Merchant&rsquo;s Operation</a></li>
        <li><span class="toc-num">3</span> <a href="#section-3">Scope of Requirement</a></li>
        <li><span class="toc-num">4</span> <a href="#section-4">Technical and Operational Expectations</a></li>
        <li><span class="toc-num">5</span> <a href="#section-5">Benefits to Each Party</a></li>
        <li><span class="toc-num">6</span> <a href="#section-6">Fee Structure (Bank, Merchant, System Provider)</a></li>
        <li><span class="toc-num">7</span> <a href="#section-7">Next Steps</a></li>
    </ul>
</div>

<div class="page">
    <div class="letterhead">
        <h1>ZIMA Solutions</h1>
        <p class="tagline">Digital transformation &amp; payment gateway | Tanzania</p>
        <p class="contact">
            Makongo, Near Ardhi University, Kinondoni &nbsp;·&nbsp; Dar es Salaam &nbsp;·&nbsp; +255 69 241 0353 &nbsp;·&nbsp; <a href="https://zima.co.tz/">zima.co.tz</a> &nbsp;·&nbsp; info@zima.co.tz &nbsp;·&nbsp; TIN: 181-314-605
        </p>
        <div class="letterhead-accent"></div>
    </div>

    <div class="meta-row">{{ now()->format('d F Y') }}</div>

    <div class="to-block">
        <div class="label">To</div>
        <div class="name">NBC Bank (National Bank of Commerce)</div>
        <div class="addr">Merchant Services / Payment Aggregator &amp; Settlement · www.nbc.co.tz</div>
    </div>

    <div class="subject">
        Request for Proposal: Supply of POS Devices Capable of Processing VISA, Mastercard and Other International Cards
    </div>
    <div class="re-merchant">
        @php
            $logoPath = base_path('pdf/images/capelogo.png');
            $logoData = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
        @endphp
        @if($logoData)
        <img src="{{ $logoData }}" alt="Cape Classique" class="logo" />
        @endif
        <span class="text"><strong>Re: Cape Classique Restaurant &amp; Wine Bar</strong><br />Sea Cliff Court Hotel &amp; Luxury Apartments, Masaki, Dar es Salaam</span>
    </div>

    <p>Dear Sir / Madam,</p>

    <div class="summary">
        ZIMA Solutions Limited writes to request a formal proposal from NBC Bank—as the chosen payment aggregator and settlement bank for our client—for the supply and deployment of Point of Sale (POS) devices at the premises of <strong>Cape Classique Restaurant &amp; Wine Bar</strong> at Sea Cliff Court Hotel &amp; Luxury Apartments, Buzwagi St. Masaki, Dar es Salaam. Our client offers all-day dining, wine bar &amp; cocktails, and 24hr room service and wishes to offer international visitors and local cardholders a seamless, secure card payment experience at the till using NBC-acquired POS devices.
    </div>

    <div class="section" id="section-1"><h2>1. Stakeholders</h2>
    <p>For clarity, the parties in this arrangement are:</p>
    <ul>
        <li><strong>Service provider (merchant)</strong>: <strong>Cape Classique Restaurant &amp; Wine Bar</strong> at Sea Cliff Court Hotel &amp; Luxury Apartments, Buzwagi St. Masaki, Dar es Salaam. They offer breakfast buffet (Monday–Friday 6:00–10:00, Saturday &amp; Sunday 6:00–11:00), all-day dining, wine bar &amp; cocktails, and 24hr room service.</li>
        <li><strong>System provider and maintainer (author of this letter)</strong>: <strong>ZIMA Solutions Limited</strong> (<a href="https://zima.co.tz/">zima.co.tz</a>). We provide and maintain the point-of-sale and restaurant management platform (Smart Dining POS) for Cape Classique, including integrations with national and payment infrastructure.</li>
        <li><strong>Payments aggregator and settlement bank</strong>: <strong>NBC Bank (National Bank of Commerce)</strong> — <a href="https://www.nbc.co.tz/">www.nbc.co.tz</a> — from whom we request POS devices and merchant acquiring for VISA, Mastercard and other international cards, with settlement in the merchant’s NBC account(s).</li>
    </ul>
    </div>

    <div class="section" id="section-2"><h2>2. About the Merchant’s Operation</h2>
    <p>
        Our client operates an integrated restaurant and point-of-sale environment that includes table service, kitchen and bar displays, online and WhatsApp ordering, reservations, and multiple payment channels. The system we provide (Smart Dining POS) already supports cash, mobile money (e.g. M-Pesa), and online card payments via gateway. To complete in-house payment options and better serve international guests and local cardholders,         the merchant requires physical POS devices—acquired and settled through NBC Bank—that can process chip, contactless, and magstripe transactions for major international card brands at the point of sale.
    </p>
    </div>

    <div class="section" id="section-3"><h2>3. Scope of Requirement</h2>
    <p>We request that NBC Bank provide (for Cape Classique):</p>
    <ul>
        <li><strong>POS terminals (hardware)</strong> suitable for restaurant and hospitality use on which the <strong>Smart Dining POS</strong> application can be installed, so that waiters carry a single device for both order-taking/serving and card transactions (e.g. mobile or handheld units such as Android-based devices that support app installation).</li>
        <li><strong>Card acceptance</strong> for at least:
            <ul>
                <li>VISA (credit and debit)</li>
                <li>Mastercard (credit and debit)</li>
                <li>Other international schemes that NBC supports (e.g. UnionPay, Amex, etc.), where commercially viable.</li>
            </ul>
        </li>
        <li><strong>Transaction types</strong>: chip & PIN, contactless (NFC), and magstripe fallback where applicable and compliant with scheme rules.</li>
        <li><strong>Merchant acquiring services</strong> from NBC Bank for the above card types, with POS devices provided to the merchant at no cost. NBC earns from charges per transaction; settlement in TZS to the merchant’s preferred NBC account(s).</li>
        <li><strong>Installation, training, and ongoing support</strong> (e.g. terminal replacement, troubleshooting, and compliance updates) as per NBC’s standard merchant terms.</li>
    </ul>
    </div>

    <div class="section" id="section-4"><h2>4. Technical and Operational Expectations</h2>
    <div class="requirements">
        <ul>
            <li>Devices to be PCI-DSS compliant and to support secure encryption (e.g. point-to-point encryption) as required by card schemes and NBC.</li>
            <li><strong>Single-device workflow</strong>: Terminals to allow installation of the <strong>Smart Dining POS</strong> application so that waiters use one device for both serving (orders, tables, menu) and card transactions. We request integrated devices or devices that support our POS app rather than separate payment-only terminals for floor staff.</li>
            <li><strong>Receipts and platform integration</strong>: Devices to support printing customer receipts and to integrate with the merchant’s existing POS/receipting system (Smart Dining POS—our platform). We request that integration with Smart Dining POS be part of the solution.</li>
            <li>Stable connectivity (GPRS/3G/4G and/or Ethernet/Wi‑Fi) suitable for the merchant’s location(s).</li>
            <li>Clear SLA for terminal availability and support, and a defined process for reporting and resolving disputes or failed transactions.</li>
        </ul>
    </div>
    </div>

    <div class="section" id="section-5"><h2>5. Benefits to Each Party</h2>
    <p>The arrangement is designed to benefit all three parties as follows.</p>
    <ul>
        <li><strong>Benefits for the Bank (NBC)</strong>: Additional merchant acquiring volume and interchange-based revenue from card transactions (VISA, Mastercard, international schemes). A formal, long-term relationship with a hospitality merchant and with ZIMA Solutions as a technology partner who can onboard further merchants. Potential for multi-outlet rollout and volume-based pricing. Settlement and float retained in NBC accounts; visibility into card acceptance growth in the hospitality segment.</li>
        <li><strong>Benefits for the Merchant (Cape Classique)</strong>: Ability to accept VISA, Mastercard and other international cards at the table, increasing revenue from tourists and business travellers. Reduced cash handling and improved security. A single device per waiter for both order-taking and payment, improving service speed and guest experience. POS devices are given to the merchant; settlement in TZS in the merchant’s NBC account.</li>
        <li><strong>Benefits for the System Provider (ZIMA Solutions)</strong>: Ability to offer card-acquiring integration as part of the Smart Dining POS platform, strengthening our value proposition to hospitality clients. A clear technical and commercial framework with NBC for device and API integration. Opportunity to replicate the model across other merchants we serve. Revenue from platform and integration services, separate from or in coordination with NBC’s fee structure.</li>
    </ul>
    </div>

    <div class="section" id="section-6"><h2>6. Fee Structure (Bank, Merchant, System Provider)</h2>
    <p>We request a transparent structure that recognises all three parties:</p>
    <ul>
        <li><strong>Bank (NBC)</strong>: POS devices are given to the merchant at no cost. NBC enjoys revenue from charges per transaction (e.g. from interchange, scheme fees or per-transaction acquiring fees). Settlement in TZS to the merchant’s NBC account. NBC to pay ZIMA Solutions 0.5% of such per-transaction charges as an onboarding commission, monthly.</li>
        <li><strong>Merchant (Cape Classique)</strong>: POS devices are provided to the merchant by NBC. The merchant pays ZIMA Solutions separately for Smart Dining POS platform, integration and support.</li>
        <li><strong>System provider (ZIMA Solutions)</strong>: Receives from NBC 0.5% of per-transaction charges as an onboarding commission, paid monthly. The merchant pays ZIMA separately for Smart Dining POS platform, integration and support.</li>
    </ul>
    <p>We request that NBC’s proposal confirm that devices are provided to the merchant, that NBC earns from per-transaction charges, and that the 0.5% onboarding commission to ZIMA is paid monthly.</p>
    </div>

    <div class="section" id="section-7"><h2>7. Next Steps</h2>
    <p>
        We request that NBC Bank provide a formal proposal including:
    </p>
    <ul>
        <li>Recommended POS device model(s) to be provided to the merchant at no cost.</li>
        <li>NBC’s per-transaction charge structure (VISA, Mastercard and other international cards) and how NBC’s revenue is derived.</li>
        <li>Settlement cycle and currency (e.g. TZS).</li>
        <li>Timeline for site survey (if required), installation, and go-live.</li>
        <li>Terms and conditions, contract duration, and exit clauses.</li>
    </ul>
    <p>
        We are available for a meeting or call at your convenience to walk through the merchant site and transaction volumes. Please indicate a point of contact and preferred next steps.
    </p>
    </div>

    <div class="closing">
        <p>Thank you for considering this request. We look forward to a lasting partnership between our client, ZIMA Solutions, and NBC Bank.</p>
    </div>

    <div class="signature">
        <p class="name">Yours faithfully,</p>
        <p class="title">ZIMA Solutions Limited</p>
        <p class="title">Makongo, Kinondoni, Dar es Salaam</p>
        <p class="title">zima.co.tz &nbsp;|&nbsp; info@zima.co.tz &nbsp;|&nbsp; +255 69 241 0353</p>
    </div>

    <div class="footer-note">
        This document is a formal request for proposal. All figures, timelines and technical requirements are subject to final agreement with NBC Bank. Confidential.
    </div>
</div>
</body>
</html>
