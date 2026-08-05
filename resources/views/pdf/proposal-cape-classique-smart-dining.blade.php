<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Smart Dining — Proposal for Cape Classique Restaurant &amp; Wine Bar</title>
    <style>
        /* ZIMA brand: blue-900 #1e3a8a, deep yellow #ca8a04 */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            color: #1e293b;
            line-height: 1.6;
            padding: 0;
            background: #fff;
        }
        /* Space at top of every page; fill page before breaking; avoid cutting paragraphs */
        @page { margin-top: 22mm; margin-bottom: 18mm; margin-left: 15mm; margin-right: 15mm; }
        .page { padding: 32px 40px 32px; padding-top: 24px; }

        /* Running header and footer on every page */
        .pdf-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 14mm;
            padding: 3mm 15mm 0;
            font-size: 9pt;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            background: #fff;
            z-index: 1;
        }
        .pdf-header .doc-title { font-weight: 600; color: #1e3a8a; }
        .pdf-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 12mm;
            padding: 2mm 15mm 0;
            font-size: 8.5pt;
            color: #64748b;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            background: #fff;
            z-index: 1;
        }
        .pdf-footer .page-number:after {
            content: "Page " counter(page) " of " counter(pages);
        }

        .letterhead {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: #fff;
            padding: 14px 24px 12px;
            margin: -28px -40px 18px -40px;
        }
        .letterhead h1 { font-size: 24pt; font-weight: bold; letter-spacing: -0.02em; margin-bottom: 4px; }
        .letterhead .tagline { font-size: 9.5pt; color: #ca8a04; font-style: italic; margin-bottom: 10px; }
        .letterhead .contact { font-size: 9pt; color: #e2e8f0; line-height: 1.5; }
        .letterhead .contact a { color: #fcd34d; text-decoration: none; }
        .letterhead-accent { height: 4px; background: linear-gradient(90deg, #ca8a04, #eab308); margin-top: 14px; }

        .meta-row { margin-bottom: 12px; font-size: 9.5pt; color: #1e3a8a; font-weight: 600; }
        .to-block {
            background: linear-gradient(to right, #eff6ff 0%, #fefce8 100%);
            border: 1px solid #bfdbfe;
            border-left: 4px solid #ca8a04;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .to-block .label { font-size: 8pt; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
        .to-block .name { font-size: 12pt; font-weight: bold; color: #1e3a8a; }
        .to-block .addr { font-size: 9.5pt; color: #475569; margin-top: 2px; }

        .subject {
            font-size: 12pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 12px 0 10px;
            padding-left: 12px;
            border-left: 4px solid #ca8a04;
            line-height: 1.35;
        }

        .intro-box {
            margin: 10px 0 12px;
            padding: 12px 16px;
            border-left: 4px solid #ca8a04;
            background: #fefce8;
            border-radius: 0 6px 6px 0;
        }
        .intro-box .text { font-size: 10.5pt; color: #1e293b; }

        h2 {
            font-size: 11.5pt;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 8px 0;
            padding-bottom: 6px;
            border-bottom: 2px solid #ca8a04;
        }
        h2:first-of-type { margin-top: 0; }
        /* Sections flow; smaller topics can share a page. Don't break inside paragraphs or flow-rows. */
        .section { margin-bottom: 2px; }
        /* Section 3 (Ordering from Home — WhatsApp) starts on its own page */
        #section-order-home { page-break-before: always; }
        h3 { font-size: 10pt; color: #334155; margin: 10px 0 4px 0; font-weight: 600; }
        p { margin-bottom: 6px; page-break-inside: avoid; }
        .summary { background: #eff6ff; border-left: 4px solid #1e3a8a; padding: 16px 20px; margin: 16px 0; border-radius: 0 6px 6px 0; }
        .summary strong { color: #1e3a8a; }
        ul { margin: 4px 0 8px 20px; }
        li { margin-bottom: 4px; }
        li strong { color: #1e3a8a; }

        .flow-row {
            display: table;
            width: 100%;
            margin: 10px 0;
            page-break-inside: avoid;
        }
        .flow-row .img-col {
            display: table-cell;
            width: 42%;
            vertical-align: top;
            padding-right: 20px;
        }
        .flow-row .img-col img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
            border: 1px solid #e2e8f0;
        }
        .flow-row .txt-col {
            display: table-cell;
            width: 58%;
            vertical-align: top;
        }
        .flow-row .caption {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 4px;
            font-style: italic;
        }
        .flow-row.full-width .img-col { display: block; width: 100%; padding-right: 0; }
        .flow-row.full-width .img-col img { max-width: 75%; margin: 0 auto 10px; display: block; }
        .flow-row.full-width .txt-col { display: block; width: 100%; }

        .benefits-grid {
            margin: 10px 0;
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        .benefits-grid .benefit {
            display: table-cell;
            width: 33%;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .benefits-grid .benefit strong { color: #1e3a8a; }
        .benefits-grid .benefit .icon { font-size: 18pt; margin-bottom: 6px; }

        .closing { margin-top: 14px; padding: 8px 0; }
        .signature { margin-top: 16px; padding-top: 12px; border-top: 2px solid #1e3a8a; }
        .signature .name { font-weight: bold; color: #1e3a8a; font-size: 11pt; }
        .signature .title { font-size: 10pt; color: #475569; margin-top: 2px; }
        .footer-note { margin-top: 18px; padding: 10px 0; border-top: 1px solid #bfdbfe; font-size: 8.5pt; color: #64748b; text-align: center; }

        .title-page { page-break-after: always; padding: 60px 40px 48px; text-align: center; }
        .title-page .brand {
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
            color: #fff;
            padding: 16px 24px;
            margin: -60px -40px 28px -40px;
        }
        .title-page .brand h1 { font-size: 22pt; font-weight: bold; }
        .title-page .brand .tagline { font-size: 9pt; color: #ca8a04; font-style: italic; margin-top: 4px; }
        .title-page .doc-title {
            font-size: 17pt;
            font-weight: bold;
            color: #1e3a8a;
            line-height: 1.35;
            margin: 28px 0 14px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }
        .title-page .doc-subtitle { font-size: 11pt; color: #475569; margin-bottom: 24px; line-height: 1.5; }
        .title-page .doc-meta { font-size: 10pt; color: #64748b; }
        .title-page .accent { height: 4px; background: linear-gradient(90deg, #ca8a04, #eab308); width: 140px; margin: 28px auto 0; border-radius: 2px; }

        .contents-page { page-break-after: always; padding: 36px 40px 48px; }
        .contents-page h2 { font-size: 13pt; color: #1e3a8a; border: none; margin-bottom: 16px; padding-bottom: 0; }
        .contents-page ul { list-style: none; margin: 0; padding: 0; }
        .contents-page li { margin-bottom: 6px; padding: 5px 0 5px 12px; border-left: 3px solid #ca8a04; page-break-inside: avoid; }
        .contents-page li a { color: #1e3a8a; text-decoration: none; font-weight: 600; }
        .contents-page .toc-num { color: #64748b; font-weight: bold; margin-right: 10px; }

        .summary, .to-block, .intro-box, .closing, .signature, .footer-note { page-break-inside: avoid; }
        h2 { page-break-after: avoid; }
        li { page-break-inside: avoid; }
        /* Keep headings with following content when possible */
        h2, h3 { page-break-after: avoid; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .flow-row .img-col img { box-shadow: none; }
        }
    </style>
</head>
<body>

<!-- Running header (every page) -->
<div class="pdf-header">
    <span class="doc-title">Smart Dining — Proposal for Cape Classique</span>
    <span style="float: right;">ZIMA Solutions</span>
</div>
<!-- Running footer with page number (every page) -->
<div class="pdf-footer">
    <div class="page-number"></div>
</div>

<!-- Title page -->
<div class="title-page">
    <div class="brand">
        <h1>ZIMA Solutions</h1>
        <p class="tagline">Digital transformation &amp; payment gateway | Tanzania</p>
    </div>
    <div class="doc-title">Smart Dining — Restaurant &amp; Wine Bar Solution</div>
    <p class="doc-subtitle">A complete proposal for Cape Classique Restaurant &amp; Wine Bar<br />Sea Cliff Court Hotel &amp; Luxury Apartments, Masaki, Dar es Salaam</p>
    <p class="doc-meta">From: ZIMA Solutions Limited<br />To: Cape Classique Restaurant &amp; Wine Bar</p>
    <p class="doc-meta" style="margin-top: 16px;">{{ now()->format('d F Y') }}</p>
    <div class="accent"></div>
</div>

<!-- Contents page -->
<div class="contents-page">
    <h2>Contents</h2>
    <ul>
        <li><span class="toc-num">1</span> <a href="#section-intro">Introduction</a></li>
        <li><span class="toc-num">2</span> <a href="#section-how">How Smart Dining Works</a></li>
        <li><span class="toc-num">3</span> <a href="#section-order-home">Ordering from Home — WhatsApp</a></li>
        <li><span class="toc-num">4</span> <a href="#section-order-pos">Ordering on Premises — Waiter with POS</a></li>
        <li><span class="toc-num">5</span> <a href="#section-dashboard">Order Management &amp; Records</a></li>
        <li><span class="toc-num">6</span> <a href="#section-pay-whatsapp">Payment via WhatsApp</a></li>
        <li><span class="toc-num">7</span> <a href="#section-pay-card">Payment by Card at the Table</a></li>
        <li><span class="toc-num">8</span> <a href="#section-why">Why Smart Dining</a></li>
        <li><span class="toc-num">9</span> <a href="#section-next">What&rsquo;s Included &amp; Next Steps</a></li>
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

    @php
        $imgBase = (isset($forPdf) && $forPdf) ? 'file://' . base_path('public/') : url('');
    @endphp

    <div class="to-block">
        <div class="label">To</div>
        <div class="name">Cape Classique Restaurant &amp; Wine Bar</div>
        <div class="addr">Sea Cliff Court Hotel &amp; Luxury Apartments, Buzwagi Street, Masaki, Dar es Salaam</div>
    </div>

    <div class="subject">
        Smart Dining — Integrated POS, WhatsApp Ordering &amp; Multi-Channel Payments
    </div>
    <div class="intro-box">
        <span class="text">ZIMA Solutions Limited is pleased to present this proposal for <strong>Smart Dining</strong> — our restaurant and point-of-sale platform that unifies table service, kitchen and bar displays, <strong>WhatsApp ordering</strong>, reservations, and multiple payment options (cash, mobile money, card, and <strong>payment via WhatsApp</strong>) in one system. This document explains how Smart Dining works for your guests and your team, with visuals to bring the experience to life.</span>
    </div>

    <p>Dear Cape Classique Team,</p>

    <div class="section" id="section-intro">
        <h2>1. Introduction</h2>
        <p>Smart Dining is designed for venues like Cape Classique: full-service restaurant, wine bar, and 24-hour room service. Your guests can <strong>order from home</strong> via WhatsApp or <strong>order at the table</strong> with a waiter using a POS device. They can <strong>pay by tapping their card</strong> at the table or <strong>pay via a secure link</strong> sent to them on WhatsApp. Kitchen and bar see orders in real time; managers see sales, orders, and reports on a single dashboard. Everything is connected.</p>
    </div>

    <div class="section" id="section-how">
        <h2>2. How Smart Dining Works</h2>
        <p>The journey is simple:</p>
        <ul>
            <li><strong>Order</strong> — Either via WhatsApp (from home or on the go) or via your waiter on a POS device at the table.</li>
            <li><strong>Prepare</strong> — Orders flow to the kitchen and bar displays in real time; staff mark items as they are prepared.</li>
            <li><strong>Serve</strong> — Waiters are notified when orders are ready and deliver to the table.</li>
            <li><strong>Pay</strong> — Guest pays by card (tap on the same POS device) or via a payment link sent to their phone (e.g. WhatsApp), or by cash or mobile money.</li>
        </ul>
        <p>The following sections show each step with real screens and devices.</p>
    </div>

    <div class="section" id="section-order-home">
        <h2>3. Ordering from Home — WhatsApp</h2>
        <p>Guests can place an order from anywhere by chatting with your restaurant on WhatsApp. They browse the menu, add items, and send their order in a simple conversation. No app download required — just WhatsApp.</p>
        <div class="flow-row">
            <div class="img-col">
                <img src="{{ $imgBase . (isset($forPdf) && $forPdf ? 'assets/iphone-whatsapp-order-request.png' : '/assets/iphone-whatsapp-order-request.png') }}" alt="WhatsApp order on iPhone" />
                <p class="caption">Guest places an order via WhatsApp from home or on the go.</p>
            </div>
            <div class="txt-col">
                <h3>How it works</h3>
                <ul>
                    <li>Guest sends a message to your business WhatsApp number.</li>
                    <li>Smart Dining responds with a menu and ordering options (powered by our conversational flow).</li>
                    <li>Guest selects items, quantities, and any special requests (e.g. &ldquo;no onions&rdquo;, &ldquo;allergy note&rdquo;).</li>
                    <li>Order is created in your system and sent to the kitchen/bar like any other order.</li>
                    <li>Ideal for takeaway, delivery, or &ldquo;order ahead&rdquo; before arriving at the restaurant.</li>
                </ul>
                <h3>What makes our WhatsApp different from flow-based chatbots</h3>
                <p>Many solutions offer fixed menus and button-only flows. Smart Dining goes further with an <strong>AI concierge</strong> that understands natural language and remembers your guests:</p>
                <ul>
                    <li><strong>Natural conversation</strong> — Guests type in plain language (&ldquo;I&rsquo;d like the usual&rdquo;, &ldquo;table for four tomorrow at 7&rdquo;, &ldquo;I&rsquo;m allergic to nuts&rdquo;). No need to follow rigid step-by-step menus.</li>
                    <li><strong>Guest memory</strong> — Returning guests are recognised: preferences, allergies, usual orders, and favourite table are used to personalise replies and avoid suggesting items they can&rsquo;t have.</li>
                    <li><strong>Bilingual</strong> — The assistant responds in English or Swahili (Kiswahili) depending on how the guest writes.</li>
                    <li><strong>Live staff connection</strong> — When the AI can&rsquo;t help or the guest asks for a person, requests are handed off to your team. Notifications go to the right role (waiter, kitchen, bar, manager) so nothing is lost.</li>
                    <li><strong>Order changes and cancellations</strong> — Guests can modify or cancel orders in chat (when the kitchen hasn&rsquo;t started); the system notifies the waiter and kitchen automatically.</li>
                    <li><strong>Receipts and feedback</strong> — Guests can ask for a PDF receipt or share feedback; the AI captures it and can alert managers when feedback is negative.</li>
                    <li><strong>Structured flow when needed</strong> — If preferred or when the AI is unavailable, a clear menu-and-buttons flow is still available so ordering always works.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section" id="section-order-pos">
        <h2>4. Ordering on Premises — Waiter with POS</h2>
        <p>When guests dine in, your waiter uses a single POS device (tablet or handheld) to take orders at the table. The same device is used later for card payment — one device per waiter for both ordering and payment.</p>
        <div class="flow-row">
            <div class="img-col">
                <img src="{{ $imgBase . (isset($forPdf) && $forPdf ? 'assets/pos-device-order-screen.png' : '/assets/pos-device-order-screen.png') }}" alt="POS device order screen" />
                <p class="caption">Waiter takes the order on a POS device at the table.</p>
            </div>
            <div class="txt-col">
                <h3>How it works</h3>
                <ul>
                    <li>Waiter selects the table and adds items from the menu (food to kitchen, drinks to bar).</li>
                    <li>Special instructions (e.g. &ldquo;medium rare&rdquo;, &ldquo;no ice&rdquo;) are captured per item.</li>
                    <li>Order is sent instantly to the kitchen and bar displays.</li>
                    <li>When the guest is ready to pay, the same device can process card tap or other payment methods.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section" id="section-dashboard">
        <h2>5. Order Management &amp; Records</h2>
        <p>Managers and staff see all orders — from WhatsApp, POS, or web — in one place. You can filter by date, table, status, or source (WhatsApp vs POS vs web). Sales reports, order history, and daily summaries are available at a glance.</p>
        <div class="flow-row">
            <div class="img-col">
                <img src="{{ $imgBase . (isset($forPdf) && $forPdf ? 'assets/website-order-records-dashboard.png' : '/assets/website-order-records-dashboard.png') }}" alt="Order records dashboard" />
                <p class="caption">Manager dashboard: orders, status, and records in one view.</p>
            </div>
            <div class="txt-col">
                <h3>What you get</h3>
                <ul>
                    <li>Unified list of all orders with status (pending, preparing, ready, delivered, paid).</li>
                    <li>Revenue and sales reports (daily, weekly, by payment method).</li>
                    <li>Staff performance and tips (for waiters).</li>
                    <li>Menu performance (best-selling items, low stock alerts if inventory is enabled).</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section" id="section-pay-whatsapp">
        <h2>6. Payment via WhatsApp</h2>
        <p>When a guest orders via WhatsApp (or when you want to send them the bill remotely), Smart Dining can send a secure payment link to their phone. They open the link and pay by card (or other methods you enable). Once payment is completed, they see a confirmation — and you see the payment in your system and in your bank.</p>
        <div class="flow-row">
            <div class="img-col">
                <img src="{{ $imgBase . (isset($forPdf) && $forPdf ? 'assets/iphone-whatsapp-payment-processed.png' : '/assets/iphone-whatsapp-payment-processed.png') }}" alt="WhatsApp payment confirmation" />
                <p class="caption">Guest receives payment confirmation on WhatsApp after paying via the link.</p>
            </div>
            <div class="txt-col">
                <h3>How it works</h3>
                <ul>
                    <li>After the order is ready (or when you choose to send the bill), the system generates a unique payment link.</li>
                    <li>The link is sent to the guest via WhatsApp (or SMS).</li>
                    <li>Guest opens the link on their phone and pays by card (Stripe) or other configured methods.</li>
                    <li>On success, the order is marked paid and the guest gets a confirmation message.</li>
                    <li>Ideal for WhatsApp orders, room service, or &ldquo;pay from your phone&rdquo; at the table.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section" id="section-pay-card">
        <h2>7. Payment by Card at the Table</h2>
        <p>For dine-in guests who prefer to pay by card, the waiter brings the same POS device to the table. The guest taps their VISA, Mastercard, or other supported card (or inserts chip, or swipes). Payment is processed securely; the merchant receives settlement in their bank account. No need for a separate payment terminal — the same device used for ordering also takes the card.</p>
        <p><strong>POS devices and international cards.</strong> Not all devices are approved for international card schemes. ZIMA Solutions is in a <strong>pre-agreement stage with NBC Bank</strong>, who will supply POS devices capable of accepting <strong>VISA, Mastercard, and other international cards</strong>. Once the agreement is in place, Cape Classique will be able to offer international visitors and local cardholders a seamless card payment experience at the table, with settlement in TZS in your NBC account. Our platform (Smart Dining) is designed to work with these devices so that waiters use one device for both order-taking and card payment.</p>
        <div class="flow-row">
            <div class="img-col">
                <img src="{{ $imgBase . (isset($forPdf) && $forPdf ? 'assets/visa-card-tap-pos-device.png' : '/assets/visa-card-tap-pos-device.png') }}" alt="VISA card tap on POS" />
                <p class="caption">Guest taps their card on the POS device at the table.</p>
            </div>
            <div class="txt-col">
                <h3>How it works</h3>
                <ul>
                    <li>Waiter selects &ldquo;Pay&rdquo; on the order and chooses &ldquo;Card&rdquo;.</li>
                    <li>Device is handed to the guest (or held at the table) for tap, chip, or swipe.</li>
                    <li>Transaction is sent to the acquirer (NBC Bank, once the agreement is live); approval is shown on the device.</li>
                    <li>Receipt can be printed or sent by email/WhatsApp if configured.</li>
                    <li>Devices supplied under our NBC pre-agreement will accept VISA, Mastercard, and other international schemes supported by NBC.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="section" id="section-why">
        <h2>8. Why Smart Dining</h2>
        <div class="benefits-grid">
            <div class="benefit">
                <div class="icon">📱</div>
                <strong>One system, many channels</strong><br />WhatsApp, POS, and web in one platform. No separate systems for online vs in-house.
            </div>
            <div class="benefit">
                <div class="icon">💳</div>
                <strong>Flexible payments</strong><br />Cash, mobile money, card at table, and payment link via WhatsApp. Suits every guest.
            </div>
            <div class="benefit">
                <div class="icon">👨‍🍳</div>
                <strong>Kitchen &amp; bar in sync</strong><br />Orders appear on kitchen and bar displays in real time; fewer errors, faster service.
            </div>
        </div>
        <p>Smart Dining is already built and running: table management, menu, orders, payments, reservations, and reports are included. For card-at-table, we are in a <strong>pre-agreement stage with NBC Bank</strong>, who will supply POS devices capable of accepting VISA, Mastercard, and other international cards—so you can offer international card acceptance once the agreement is in place. Our team handles setup, training, and ongoing support so Cape Classique can focus on hospitality.</p>
    </div>

    <div class="section" id="section-next">
        <h2>9. What&rsquo;s Included &amp; Next Steps</h2>
        <p>We propose the following for Cape Classique:</p>
        <ul>
            <li><strong>Smart Dining POS platform</strong> — Backend, API, web dashboard (manager, kitchen, bar), and support for Android POS devices for waiters.</li>
            <li><strong>WhatsApp ordering with AI concierge</strong> — Natural-language ordering, guest memory, bilingual (English/Swahili), reservations, payment links, and staff handoff—not just fixed button flows. Optional structured menu flow as fallback.</li>
            <li><strong>Payment integration</strong> — Cash, mobile money (e.g. M-Pesa), and online card payments (Stripe). Card-at-table via POS devices: ZIMA is in a <strong>pre-agreement with NBC Bank</strong>, who will supply POS devices capable of accepting <strong>VISA, Mastercard, and other international cards</strong>; once the agreement is finalised, Cape Classique will be able to offer these at the table with settlement in TZS.</li>
            <li><strong>Training and support</strong> — On-site or remote training for managers, waiters, kitchen and bar; ongoing technical support.</li>
        </ul>
        <p>We would be glad to schedule a demo at your premises or online, and to provide a detailed commercial proposal (pricing, timeline, and contract terms) tailored to Cape Classique. Please contact us to arrange a meeting.</p>
    </div>

    <div class="closing">
        <p>Thank you for considering Smart Dining. We look forward to partnering with Cape Classique Restaurant &amp; Wine Bar and to supporting your growth with a modern, integrated POS and ordering experience.</p>
    </div>

    <div class="signature">
        <p class="name">Yours sincerely,</p>
        <p class="title">ZIMA Solutions Limited</p>
        <p class="title">Makongo, Kinondoni, Dar es Salaam</p>
        <p class="title">zima.co.tz &nbsp;|&nbsp; info@zima.co.tz &nbsp;|&nbsp; +255 69 241 0353</p>
    </div>

    <div class="footer-note">
        This document is a proposal from ZIMA Solutions Limited. All features and commercial terms are subject to a separate agreement. Confidential.
    </div>
</div>
</body>
</html>
