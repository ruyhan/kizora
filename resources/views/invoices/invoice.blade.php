@php
    $primary = $settings['primary_color'] ?? '#17181B';
    $secondary = $settings['secondary_color'] ?? '#ED1C24';
    // dompdf's bundled font has no ৳ (Taka) glyph, so use the ASCII "Tk" — renders cleanly in both PDF & browser.
    $money = fn ($v) => 'Tk ' . number_format((float) $v, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #2b2b2b; font-size: 12px; line-height: 1.5; }
        .wrap { padding: 36px 40px; }
        .row { width: 100%; }
        .row:after { content: ""; display: table; clear: both; }
        .col-left { float: left; width: 55%; }
        .col-right { float: right; width: 45%; text-align: right; }
        h1 { font-size: 26px; letter-spacing: 1px; color: {{ $primary }}; }
        .muted { color: #777; }
        .brand { font-size: 20px; font-weight: bold; color: {{ $primary }}; }
        .pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; color: #fff; background: {{ $secondary }}; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th { background: {{ $primary }}; color: #fff; text-align: left; padding: 9px 10px; font-size: 11px; }
        table.items th.r, table.items td.r { text-align: right; }
        table.items td { padding: 9px 10px; border-bottom: 1px solid #eee; }
        .totals { width: 50%; float: right; margin-top: 10px; }
        .totals td { padding: 6px 10px; }
        .totals .grand td { border-top: 2px solid {{ $primary }}; font-size: 15px; font-weight: bold; color: {{ $primary }}; }
        .section-title { color: {{ $primary }}; font-weight: bold; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
        .box { border: 1px solid #eee; border-radius: 8px; padding: 12px 14px; }
        .info-box { min-height: 96px; }
        .footer { margin-top: 28px; border-top: 2px solid {{ $secondary }}; padding-top: 14px; text-align: center; color: #666; }
        .accent-bar { height: 6px; background: {{ $secondary }}; }
    </style>
</head>
<body>
    <div class="accent-bar"></div>
    <div class="wrap">

        <!-- Header -->
        <table style="width:100%; margin-bottom: 28px;">
            <tr>
                <td style="vertical-align: top; width: 58%;">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" alt="logo" style="max-height: 50px; max-width: 200px; margin-bottom: 8px;">
                    @else
                        <div class="brand">{{ $settings['company_name'] ?? 'Kizora' }}</div>
                    @endif
                    <div class="muted" style="margin-top: 4px; line-height: 1.6;">
                        {!! nl2br(e($settings['company_address'] ?? '')) !!}<br>
                        @if(!empty($settings['company_phone'])) {{ $settings['company_phone'] }}<br> @endif
                        @if(!empty($settings['company_email'])) {{ $settings['company_email'] }}<br> @endif
                        @if(!empty($settings['company_website'])) {{ $settings['company_website'] }} @endif
                    </div>
                </td>
                <td style="vertical-align: top; width: 42%; text-align: right;">
                    <h1>{{ $settings['invoice_title'] ?? 'INVOICE' }}</h1>
                    @if(!empty($settings['header_text']))<div class="muted">{{ $settings['header_text'] }}</div>@endif
                    <div style="margin-top: 10px; line-height: 1.7;">
                        <span class="muted">Invoice No.</span> <strong>{{ $invoice->invoice_number }}</strong><br>
                        <span class="muted">Date:</span> {{ $order->created_at->format('d M Y') }}<br>
                        <span class="pill" style="margin-top: 6px;">{{ ucfirst($order->source ?? 'online') }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Bill to / Payment — equal-height columns -->
        <table style="width:100%; border-collapse: separate; border-spacing: 0; margin-bottom: 22px;">
            <tr>
                <td style="width:50%; vertical-align: top; padding-right: 9px;">
                    <div class="box info-box">
                        <div class="section-title">Bill To</div>
                        <strong>{{ $customer['name'] }}</strong><br>
                        @if($customer['phone'])<span class="muted">{{ $customer['phone'] }}</span><br>@endif
                        @if($customer['email'])<span class="muted">{{ $customer['email'] }}</span><br>@endif
                        @if($order->delivery_address)<span class="muted">{{ $order->delivery_address }}</span><br>@endif
                        @if($order->delivery_area)<span class="muted">Area: {{ $order->delivery_area }}</span><br>@endif
                        @if($order->delivery_city)<span class="muted">City: {{ $order->delivery_city }}</span>@endif
                    </div>
                </td>
                <td style="width:50%; vertical-align: top; padding-left: 9px;">
                    <div class="box info-box">
                        <div class="section-title">Payment</div>
                        <span class="muted">Method:</span> {{ strtoupper(str_replace('_', ' ', $order->payment_method)) }}<br>
                        <span class="muted">Status:</span> {{ ucfirst($order->payment_status) }}
                    </div>
                </td>
            </tr>
        </table>

        <!-- Items -->
        <table class="items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="r">Qty</th>
                    <th class="r">Unit Price</th>
                    <th class="r">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            {{ $item->product_name }}
                            @if(!empty($item->variations))
                                <br><span class="muted" style="font-size:10px;">
                                    @foreach($item->variations as $v){{ $v['name'] ?? '' }}: {{ $v['label'] ?? $v['value'] ?? '' }}@if(!$loop->last) · @endif @endforeach
                                </span>
                            @endif
                        </td>
                        <td class="r">{{ $item->quantity }}</td>
                        <td class="r">{{ $money($item->price) }}</td>
                        <td class="r">{{ $money($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals">
            <tr><td class="muted">Subtotal</td><td class="r">{{ $money($order->subtotal) }}</td></tr>
            <tr><td class="muted">Delivery</td><td class="r">{{ (float) $order->delivery_charge == 0.0 ? 'FREE' : $money($order->delivery_charge) }}</td></tr>
            @if((float) $order->discount > 0)
                <tr><td class="muted">Discount</td><td class="r">- {{ $money($order->discount) }}</td></tr>
            @endif
            <tr class="grand"><td>Grand Total</td><td class="r">{{ $money($order->total) }}</td></tr>
        </table>
        <div style="clear: both;"></div>

        @if(!empty($settings['notes']) || !empty($order->notes))
            <div style="margin-top: 22px;">
                <div class="section-title">Notes</div>
                <div class="muted">{{ $order->notes ?: $settings['notes'] }}</div>
            </div>
        @endif

        @if(!empty($settings['terms']))
            <div style="margin-top: 16px;">
                <div class="section-title">Terms &amp; Conditions</div>
                <div class="muted">{{ $settings['terms'] }}</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <strong style="color: {{ $primary }};">{{ $settings['footer_message'] ?? 'Thank you for shopping with us!' }}</strong><br>
            <span class="muted">{{ $settings['company_name'] ?? 'Kizora' }}
                @if(!empty($settings['company_website'])) · {{ $settings['company_website'] }} @endif
                @if(!empty($settings['company_phone'])) · {{ $settings['company_phone'] }} @endif
            </span>
        </div>
    </div>

    @if($print)
        <script>window.onload = () => { window.print(); }</script>
    @endif
</body>
</html>
