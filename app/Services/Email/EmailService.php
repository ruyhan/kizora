<?php

namespace App\Services\Email;

use App\Mail\TemplatedMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Order;
use App\Support\PageContent;
use Illuminate\Support\Facades\Mail;

/**
 * Central transactional-email service: a registry of editable templates (with
 * default subject/body + the variables each one supports), rendering with the
 * shared responsive layout, and queued sending with per-email logging.
 */
class EmailService
{
    public function __construct(private readonly EmailSettingService $settings) {}

    /** Status → template key map used by the order lifecycle. */
    public const STATUS_TEMPLATES = [
        'processing'       => 'order_processing',
        'out_for_delivery' => 'order_shipped',
        'completed'        => 'order_delivered',
        'cancelled'        => 'order_cancelled',
    ];

    /** Placeholder in the rendered HTML that is swapped for the CID (or base64) logo at send time. */
    private const LOGO_TOKEN = '__KZ_LOGO_SRC__';

    /** @return array<string,array> registry of default templates. */
    public function registry(): array
    {
        return [
            'order_placed_customer' => [
                'name'      => 'Customer — Order Confirmation',
                'group'     => 'Customer',
                'subject'   => 'Order confirmed — {order_number}',
                'variables' => ['customer_name', 'order_number', 'order_date', 'billing_address', 'shipping_address', 'product_table', 'subtotal', 'shipping_charge', 'discount', 'grand_total', 'payment_method', 'expected_delivery', 'tracking_link', 'support_email'],
                'body'      => <<<HTML
<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">Thank you for your order, {customer_name}!</h1>
<p style="margin:0 0 20px;color:#52525b;font-size:15px;line-height:1.6;">Your order has been received and is now being prepared. Here's a summary of your purchase.</p>

<table role="presentation" width="100%" style="margin:0 0 20px;border-collapse:collapse;background:#f7f7f8;border-radius:10px;">
  <tr>
    <td style="padding:14px 16px;font-size:13px;color:#52525b;">Order number<br><strong style="color:#17181B;font-size:15px;">{order_number}</strong></td>
    <td style="padding:14px 16px;font-size:13px;color:#52525b;">Order date<br><strong style="color:#17181B;font-size:15px;">{order_date}</strong></td>
    <td style="padding:14px 16px;font-size:13px;color:#52525b;">Payment<br><strong style="color:#17181B;font-size:15px;">{payment_method}</strong></td>
  </tr>
</table>

{product_table}

<table role="presentation" width="100%" style="margin:4px 0 20px;border-collapse:collapse;font-size:14px;color:#52525b;">
  <tr><td style="padding:4px 0;">Subtotal</td><td style="padding:4px 0;text-align:right;color:#17181B;">{subtotal}</td></tr>
  <tr><td style="padding:4px 0;">Shipping</td><td style="padding:4px 0;text-align:right;color:#17181B;">{shipping_charge}</td></tr>
  <tr><td style="padding:4px 0;">Discount</td><td style="padding:4px 0;text-align:right;color:#16a34a;">-{discount}</td></tr>
  <tr><td style="padding:10px 0 0;border-top:1px solid #e7e7ea;font-weight:700;color:#17181B;font-size:16px;">Grand total</td><td style="padding:10px 0 0;border-top:1px solid #e7e7ea;text-align:right;font-weight:700;color:#17181B;font-size:16px;">{grand_total}</td></tr>
</table>

<table role="presentation" width="100%" style="margin:0 0 24px;border-collapse:collapse;">
  <tr>
    <td width="50%" style="padding:0 8px 0 0;vertical-align:top;font-size:13px;color:#52525b;">
      <strong style="color:#17181B;display:block;margin-bottom:4px;">Billing address</strong>{billing_address}
    </td>
    <td width="50%" style="padding:0 0 0 8px;vertical-align:top;font-size:13px;color:#52525b;">
      <strong style="color:#17181B;display:block;margin-bottom:4px;">Shipping address</strong>{shipping_address}
    </td>
  </tr>
</table>

<p style="margin:0 0 22px;color:#52525b;font-size:14px;">Estimated delivery: <strong style="color:#17181B;">{expected_delivery}</strong></p>

<p style="margin:0 0 6px;"><a href="{tracking_link}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Track your order</a>&nbsp;&nbsp;<a href="mailto:{support_email}" style="display:inline-block;background:#ffffff;color:#17181B;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:12px 23px;border-radius:10px;border:1px solid #e7e7ea;">Contact support</a></p>
HTML,
            ],

            'order_received_admin' => [
                'name'      => 'Admin — New Order Received',
                'group'     => 'Admin',
                'subject'   => 'New order {order_number} — {grand_total}',
                'variables' => ['customer_name', 'customer_phone', 'customer_email', 'order_number', 'order_date', 'order_total', 'grand_total', 'payment_method', 'product_table', 'order_link'],
                'body'      => <<<HTML
<div style="background:#FEECEC;color:#B00C13;font-weight:700;font-size:13px;letter-spacing:.04em;text-transform:uppercase;padding:10px 14px;border-radius:8px;margin:0 0 18px;">🔔 New order received</div>
<h1 style="margin:0 0 16px;font-size:20px;color:#17181B;">Order {order_number}</h1>

<table role="presentation" width="100%" style="margin:0 0 18px;border-collapse:collapse;background:#f7f7f8;border-radius:10px;font-size:14px;color:#52525b;">
  <tr><td style="padding:10px 16px;">Customer</td><td style="padding:10px 16px;text-align:right;color:#17181B;font-weight:600;">{customer_name}</td></tr>
  <tr><td style="padding:10px 16px;">Phone</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{customer_phone}</td></tr>
  <tr><td style="padding:10px 16px;">Email</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{customer_email}</td></tr>
  <tr><td style="padding:10px 16px;">Payment</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{payment_method}</td></tr>
  <tr><td style="padding:10px 16px;">Order value</td><td style="padding:10px 16px;text-align:right;color:#17181B;font-weight:700;">{grand_total}</td></tr>
</table>

{product_table}

<p style="margin:18px 0 0;"><a href="{order_link}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">View order details</a></p>
HTML,
            ],

            'contact_received_admin' => [
                'name'      => 'Admin — New Contact Message',
                'group'     => 'Admin',
                'subject'   => 'New contact message from {contact_name}',
                'variables' => ['contact_name', 'contact_email', 'contact_phone', 'contact_subject', 'contact_message'],
                'body'      => <<<HTML
<div style="background:#EEF2FF;color:#4338CA;font-weight:700;font-size:13px;letter-spacing:.04em;text-transform:uppercase;padding:10px 14px;border-radius:8px;margin:0 0 18px;">✉️ New contact message</div>
<h1 style="margin:0 0 16px;font-size:20px;color:#17181B;">{contact_subject}</h1>

<table role="presentation" width="100%" style="margin:0 0 18px;border-collapse:collapse;background:#f7f7f8;border-radius:10px;font-size:14px;color:#52525b;">
  <tr><td style="padding:10px 16px;">Name</td><td style="padding:10px 16px;text-align:right;color:#17181B;font-weight:600;">{contact_name}</td></tr>
  <tr><td style="padding:10px 16px;">Email</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{contact_email}</td></tr>
  <tr><td style="padding:10px 16px;">Phone</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{contact_phone}</td></tr>
</table>

<p style="margin:0 0 6px;color:#9b9ba3;font-size:13px;text-transform:uppercase;letter-spacing:.04em;">Message</p>
<div style="background:#ffffff;border:1px solid #ececf0;border-radius:10px;padding:16px;color:#52525b;font-size:15px;line-height:1.7;white-space:pre-wrap;">{contact_message}</div>

<p style="margin:18px 0 0;"><a href="mailto:{contact_email}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Reply to {contact_name}</a></p>
HTML,
            ],

            'order_processing' => [
                'name'      => 'Customer — Order Processing',
                'group'     => 'Customer',
                'subject'   => 'Your order {order_number} is being processed',
                'variables' => ['customer_name', 'order_number', 'tracking_link'],
                'body'      => <<<HTML
<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">We're preparing your order, {customer_name}</h1>
<p style="margin:0 0 18px;color:#52525b;font-size:15px;line-height:1.6;">Good news — your order <strong style="color:#17181B;">{order_number}</strong> is now being processed and packed with care. We'll let you know the moment it ships.</p>
<div style="display:inline-block;background:#ECECEE;color:#17181B;font-weight:600;font-size:13px;padding:8px 14px;border-radius:999px;margin:0 0 22px;">Status: Processing</div>
<p style="margin:0;"><a href="{tracking_link}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Track your order</a></p>
HTML,
            ],

            'order_shipped' => [
                'name'      => 'Customer — Order Shipped',
                'group'     => 'Customer',
                'subject'   => 'Your order {order_number} has shipped',
                'variables' => ['customer_name', 'order_number', 'courier_name', 'tracking_number', 'tracking_url', 'estimated_delivery_date', 'tracking_link'],
                'body'      => <<<HTML
<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">Your order is on its way! 📦</h1>
<p style="margin:0 0 18px;color:#52525b;font-size:15px;line-height:1.6;">Hi {customer_name}, your order <strong style="color:#17181B;">{order_number}</strong> has been shipped.</p>

<table role="presentation" width="100%" style="margin:0 0 20px;border-collapse:collapse;background:#f7f7f8;border-radius:10px;font-size:14px;color:#52525b;">
  <tr><td style="padding:10px 16px;">Courier</td><td style="padding:10px 16px;text-align:right;color:#17181B;font-weight:600;">{courier_name}</td></tr>
  <tr><td style="padding:10px 16px;">Tracking number</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{tracking_number}</td></tr>
  <tr><td style="padding:10px 16px;">Estimated delivery</td><td style="padding:10px 16px;text-align:right;color:#17181B;">{estimated_delivery_date}</td></tr>
</table>

<p style="margin:0;"><a href="{tracking_url}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Track shipment</a>&nbsp;&nbsp;<a href="{tracking_link}" style="display:inline-block;background:#ffffff;color:#17181B;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:12px 23px;border-radius:10px;border:1px solid #e7e7ea;">View order</a></p>
HTML,
            ],

            'order_delivered' => [
                'name'      => 'Customer — Order Delivered',
                'group'     => 'Customer',
                'subject'   => 'Your order {order_number} has been delivered',
                'variables' => ['customer_name', 'order_number', 'delivered_date', 'review_link', 'reorder_link'],
                'body'      => <<<HTML
<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">Delivered — enjoy! 🎉</h1>
<p style="margin:0 0 18px;color:#52525b;font-size:15px;line-height:1.6;">Hi {customer_name}, your order <strong style="color:#17181B;">{order_number}</strong> was delivered on <strong style="color:#17181B;">{delivered_date}</strong>. We hope you love it!</p>
<p style="margin:0 0 22px;color:#52525b;font-size:15px;line-height:1.6;">Your feedback helps us and other shoppers — would you take a moment to leave a review?</p>
<p style="margin:0;"><a href="{review_link}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Leave a review</a>&nbsp;&nbsp;<a href="{reorder_link}" style="display:inline-block;background:#ffffff;color:#17181B;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:12px 23px;border-radius:10px;border:1px solid #e7e7ea;">Reorder</a></p>
HTML,
            ],

            'order_cancelled' => [
                'name'      => 'Customer — Order Cancelled',
                'group'     => 'Customer',
                'subject'   => 'Your order {order_number} has been cancelled',
                'variables' => ['customer_name', 'order_number', 'cancel_reason', 'support_email'],
                'body'      => <<<HTML
<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">Your order has been cancelled</h1>
<p style="margin:0 0 18px;color:#52525b;font-size:15px;line-height:1.6;">Hi {customer_name}, your order <strong style="color:#17181B;">{order_number}</strong> has been cancelled.</p>
<table role="presentation" width="100%" style="margin:0 0 20px;border-collapse:collapse;background:#FEECEC;border-radius:10px;">
  <tr><td style="padding:14px 16px;font-size:14px;color:#B00C13;"><strong>Reason:</strong> {cancel_reason}</td></tr>
</table>
<p style="margin:0 0 22px;color:#52525b;font-size:15px;line-height:1.6;">If this was a mistake or you have any questions, our team is here to help.</p>
<p style="margin:0;"><a href="mailto:{support_email}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Contact support</a></p>
HTML,
            ],

            'password_reset' => [
                'name'      => 'Account — Password Reset',
                'group'     => 'Account',
                'subject'   => 'Reset your password',
                'variables' => ['customer_name', 'reset_link'],
                'body'      => <<<HTML
<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">Reset your password</h1>
<p style="margin:0 0 18px;color:#52525b;font-size:15px;line-height:1.6;">Hi {customer_name}, we received a request to reset your password. Click the button below to choose a new one — this link expires in 60 minutes.</p>
<p style="margin:0 0 22px;"><a href="{reset_link}" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;line-height:1;padding:13px 24px;border-radius:10px;">Reset password</a></p>
<p style="margin:0;color:#9b9ba3;font-size:13px;line-height:1.6;">If you didn't request a password reset, you can safely ignore this email — your password won't change.</p>
HTML,
            ],
        ];
    }

    // ── Template provisioning ────────────────────────────────────────────────

    /** Ensure every registry template has a DB row (idempotent). */
    public function ensureSeeded(): void
    {
        $existing = EmailTemplate::pluck('key')->all();
        foreach ($this->registry() as $key => $def) {
            if (! in_array($key, $existing, true)) {
                EmailTemplate::create([
                    'key'        => $key,
                    'name'       => $def['name'],
                    'subject'    => $def['subject'],
                    'body_html'  => $def['body'],
                    'is_enabled' => true,
                    'is_custom'  => false,
                ]);
            }
        }
    }

    public function template(string $key): ?EmailTemplate
    {
        $this->ensureSeeded();
        return EmailTemplate::where('key', $key)->first();
    }

    public function reset(string $key): ?EmailTemplate
    {
        $def = $this->registry()[$key] ?? null;
        $tpl = $this->template($key);
        if ($def && $tpl) {
            $tpl->update(['subject' => $def['subject'], 'body_html' => $def['body'], 'name' => $def['name']]);
        }
        return $tpl;
    }

    public function duplicate(string $key): EmailTemplate
    {
        $src = $this->template($key);
        return EmailTemplate::create([
            'key'        => $key . '-copy-' . time(),
            'name'       => ($src?->name ?? 'Template') . ' (copy)',
            'subject'    => $src?->subject ?? '',
            'body_html'  => $src?->body_html ?? '',
            'is_enabled' => false,
            'is_custom'  => true,
        ]);
    }

    /** Variables available to a template (registry, or nearest match for copies). */
    public function variablesFor(string $key): array
    {
        $reg = $this->registry();
        if (isset($reg[$key])) {
            return $reg[$key]['variables'];
        }
        foreach ($reg as $rk => $def) {
            if (str_starts_with($key, $rk)) {
                return $def['variables'];
            }
        }
        return ['customer_name', 'order_number'];
    }

    // ── Rendering ────────────────────────────────────────────────────────────

    /**
     * @return array{subject:string,html:string,text:string,logoPath:?string,logoFallback:?string}
     * When $forSend is true the logo src becomes a token that TemplatedMail swaps
     * for an inline CID (Gmail-friendly), falling back to base64/url.
     */
    public function render(string $key, array $vars, bool $forSend = false): array
    {
        $tpl = $this->template($key);
        $subject = $this->interpolate($tpl?->subject ?? '', $vars);
        $bodyHtml = $this->interpolate($tpl?->body_html ?? '', $vars);

        $brand = $this->settings->brand();
        $logoFallback = $brand['logo'];   // base64 data URI (or url) — used if CID can't be embedded
        $logoPath = null;
        if ($forSend && $logoFallback) {
            $logoPath = $this->settings->logoFile();   // local file → CID embed
            $brand['logo'] = self::LOGO_TOKEN;
        }

        $html = view('emails.layout', [
            'content' => $bodyHtml,
            'brand'   => $brand,
            'subject' => $subject,
        ])->render();

        return [
            'subject'      => $subject,
            'html'         => $html,
            'text'         => $this->toPlainText($bodyHtml),
            'logoPath'     => $logoPath,
            'logoFallback' => $logoFallback,
        ];
    }

    private function interpolate(string $template, array $vars): string
    {
        $replace = [];
        foreach ($vars as $k => $v) {
            $replace['{' . $k . '}'] = is_scalar($v) ? (string) $v : '';
        }
        return strtr($template, $replace);
    }

    private function toPlainText(string $html): string
    {
        $text = preg_replace('/<(br|\/p|\/h[1-6]|\/tr|\/div|\/li)[^>]*>/i', "\n", $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace("/[ \t]+/", ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    /** Render an arbitrary (possibly unsaved) subject+body with sample data — for the live preview. */
    public function renderPreview(string $subject, string $bodyHtml, array $vars): string
    {
        return view('emails.layout', [
            'content' => $this->interpolate($bodyHtml, $vars),
            'brand'   => $this->settings->brand(),
            'subject' => $this->interpolate($subject, $vars),
        ])->render();
    }

    /** Realistic placeholder values for previews and template test sends. */
    public function sampleVars(): array
    {
        $c = $this->currency();
        $addr = 'Ayesha Rahman<br>House 7, Road 12<br>Banani, Dhaka<br>+880 1700-000000';
        $row = fn ($name, $sub, $qty, $price, $total) =>
            '<tr><td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#17181B;">' . $name
            . ($sub ? '<br><span style="color:#9b9ba3;font-size:12px;">' . $sub . '</span>' : '')
            . '</td><td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#52525b;text-align:center;">' . $qty
            . '</td><td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#52525b;text-align:right;">' . $c . $price
            . '</td><td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#17181B;text-align:right;">' . $c . $total . '</td></tr>';
        $table = '<table role="presentation" width="100%" style="border-collapse:collapse;margin:0 0 16px;">'
            . '<tr><th align="left" style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;color:#52525b;">Product</th>'
            . '<th style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;color:#52525b;">Qty</th>'
            . '<th align="right" style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;color:#52525b;">Unit price</th>'
            . '<th align="right" style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;color:#52525b;">Total</th></tr>'
            . $row('Kizora Slim Travel Sling', 'Color: <span style="display:inline-block;width:11px;height:11px;border-radius:3px;border:1px solid #d4d4d8;background:#ED1C24;vertical-align:middle;margin:0 2px;"></span> Red, Size: M', 1, '990.00', '990.00')
            . $row('Kizora Bottle Carry Sleeve', '', 1, '900.00', '900.00')
            . '</table>';

        return [
            'customer_name'           => 'Ayesha Rahman',
            'order_number'            => 'ORD-10042',
            'order_date'              => now()->format('d M Y'),
            'billing_address'         => $addr,
            'shipping_address'        => $addr,
            'product_table'           => $table,
            'subtotal'                => $c . '1,890.00',
            'shipping_charge'         => $c . '80.00',
            'discount'                => $c . '100.00',
            'grand_total'             => $c . '1,870.00',
            'order_total'             => $c . '1,870.00',
            'payment_method'          => 'Cash on Delivery',
            'expected_delivery'       => 'Within 3–5 business days',
            'tracking_link'           => url('/track-order?order_number=ORD-10042'),
            'support_email'           => $this->settings->brand()['support_email'] ?: 'support@example.com',
            'customer_phone'          => '+880 1700-000000',
            'customer_email'          => 'customer@example.com',
            'courier_name'            => 'Pathao Courier',
            'tracking_number'         => 'TRK123456789',
            'tracking_url'            => 'https://courier.example.com/track/TRK123456789',
            'estimated_delivery_date' => now()->addDays(3)->format('d M Y'),
            'delivered_date'          => now()->format('d M Y'),
            'cancel_reason'           => 'Customer requested cancellation',
            'review_link'             => url('/track-order?order_number=ORD-10042'),
            'reorder_link'            => url('/shop'),
            'order_link'              => url('/admin/orders/42'),
        ];
    }

    /** Synchronous test send of a template (immediate success/failure for the admin). */
    public function sendTest(string $key, string $to): array
    {
        $r = $this->render($key, $this->sampleVars(), true);
        $log = EmailLog::create([
            'template_key' => $key,
            'mailable'     => TemplatedMail::class,
            'recipient'    => $to,
            'subject'      => '[TEST] ' . $r['subject'],
            'status'       => 'queued',
        ]);
        try {
            $replyTo = $this->settings->smtp(false)['reply_to'] ?: null;
            Mail::to($to)->sendNow(new TemplatedMail('[TEST] ' . $r['subject'], $r['html'], $r['text'], $log->id, $replyTo, $r['logoPath'], $r['logoFallback']));
            return ['ok' => true, 'message' => 'Test email sent to ' . $to];
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    // ── Sending ──────────────────────────────────────────────────────────────

    public function send(string $key, ?string $to, array $vars, ?int $orderId = null): ?EmailLog
    {
        if (! $to || ! $this->settings->enabled()) {
            return null;
        }
        $tpl = $this->template($key);
        if (! $tpl || ! $tpl->is_enabled) {
            return null;
        }

        $r = $this->render($key, $vars, true);

        $log = EmailLog::create([
            'template_key' => $key,
            'mailable'     => TemplatedMail::class,
            'recipient'    => $to,
            'subject'      => $r['subject'],
            'status'       => 'queued',
            'order_id'     => $orderId,
            'variables'    => array_filter($vars, 'is_scalar'),
        ]);

        $replyTo = $this->settings->smtp(false)['reply_to'] ?: null;
        $this->deliver($to, new TemplatedMail($r['subject'], $r['html'], $r['text'], $log->id, $replyTo, $r['logoPath'], $r['logoFallback']), $log);

        return $log;
    }

    /**
     * Deliver a mailable. Sends immediately (sendNow) by default so the email
     * goes out within the request and the log flips to "sent" via the
     * MessageSent listener. Only queues when background sending is explicitly
     * enabled (which requires a running queue worker).
     */
    private function deliver(string $to, TemplatedMail $mailable, EmailLog $log): void
    {
        try {
            if ($this->settings->queueEnabled()) {
                Mail::to($to)->queue($mailable);
            } else {
                Mail::to($to)->sendNow($mailable);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    /** Re-send a previous log entry. */
    public function resend(EmailLog $log): EmailLog
    {
        $key = $log->template_key;
        $vars = $log->variables ?? [];
        // Re-render fresh so edited templates take effect.
        $r = $key
            ? $this->render($key, $vars, true)
            : ['subject' => $log->subject, 'html' => '', 'text' => '', 'logoPath' => null, 'logoFallback' => null];

        $new = EmailLog::create([
            'template_key' => $key,
            'mailable'     => TemplatedMail::class,
            'recipient'    => $log->recipient,
            'subject'      => $r['subject'] ?: $log->subject,
            'status'       => 'queued',
            'order_id'     => $log->order_id,
            'variables'    => $vars,
        ]);

        $replyTo = $this->settings->smtp(false)['reply_to'] ?: null;
        $this->deliver($log->recipient, new TemplatedMail($new->subject, $r['html'], $r['text'], $new->id, $replyTo, $r['logoPath'], $r['logoFallback']), $new);

        return $new;
    }

    // ── Variable builders ────────────────────────────────────────────────────

    private function currency(): string
    {
        return app(PageContent::class)->get('settings')['currency_symbol'] ?? '৳';
    }

    private function money($v): string
    {
        return $this->currency() . number_format((float) $v, 2);
    }

    public function paymentLabel(?string $method): string
    {
        return match ($method) {
            'cod'        => 'Cash on Delivery',
            'bkash'      => 'bKash',
            'nagad'      => 'Nagad',
            'sslcommerz' => 'Card / SSLCommerz',
            default      => ucfirst((string) $method),
        };
    }

    /**
     * Human-readable variation label. For colours we show a colour swatch plus a
     * friendly colour name (the saved label, or the nearest common colour to the
     * hex) instead of the raw hex code.
     */
    private function variationLabel(array $v): string
    {
        $name  = e($v['name'] ?? '');
        $value = (string) ($v['value'] ?? '');
        $label = (string) ($v['label'] ?? '');

        if (($v['type'] ?? '') === 'color' && preg_match('/^#?[0-9a-fA-F]{6}$/', trim($value))) {
            $swatch = '<span style="display:inline-block;width:11px;height:11px;border-radius:3px;border:1px solid #d4d4d8;background:' . e($value) . ';vertical-align:middle;margin:0 2px;"></span>';
            $human  = ($label && strcasecmp($label, $value) !== 0) ? $label : ($this->colorName($value) ?? '');

            return $name . ': ' . $swatch . ($human ? ' ' . e($human) : '');
        }

        return $name . ': ' . e($label ?: $value);
    }

    /** Nearest common colour name for a hex value (e.g. #ED1C24 → Red). */
    private function colorName(string $hex): ?string
    {
        $hex = ltrim(trim($hex), '#');
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }
        [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];

        $palette = [
            'Black' => [0, 0, 0], 'White' => [255, 255, 255], 'Gray' => [128, 128, 128], 'Silver' => [192, 192, 192],
            'Red' => [220, 20, 30], 'Maroon' => [128, 0, 0], 'Orange' => [255, 140, 0], 'Gold' => [212, 175, 55],
            'Yellow' => [240, 220, 20], 'Olive' => [128, 128, 0], 'Green' => [34, 139, 34], 'Teal' => [0, 128, 128],
            'Blue' => [30, 80, 200], 'Navy' => [20, 30, 90], 'Purple' => [128, 0, 128], 'Pink' => [255, 105, 180],
            'Brown' => [120, 72, 40], 'Beige' => [222, 200, 160],
        ];

        $best = null;
        $bestD = PHP_INT_MAX;
        foreach ($palette as $nm => [$pr, $pg, $pb]) {
            $d = ($r - $pr) ** 2 + ($g - $pg) ** 2 + ($b - $pb) ** 2;
            if ($d < $bestD) {
                $bestD = $d;
                $best = $nm;
            }
        }

        return $best;
    }

    public function productTable(Order $order): string
    {
        $rows = '';
        foreach ($order->items as $i) {
            $variations = '';
            if (! empty($i->variations) && is_array($i->variations)) {
                $parts = array_map(fn ($v) => $this->variationLabel($v), $i->variations);
                $variations = '<br><span style="color:#9b9ba3;font-size:12px;">' . implode(', ', $parts) . '</span>';
            }
            $rows .= '<tr>'
                . '<td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#17181B;">' . e($i->product_name) . $variations . '</td>'
                . '<td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#52525b;text-align:center;">' . (int) $i->quantity . '</td>'
                . '<td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#52525b;text-align:right;">' . $this->money($i->price) . '</td>'
                . '<td style="padding:12px 8px;border-bottom:1px solid #e7e7ea;font-size:14px;color:#17181B;text-align:right;">' . $this->money($i->total) . '</td>'
                . '</tr>';
        }

        return '<table role="presentation" width="100%" style="border-collapse:collapse;margin:0 0 16px;">'
            . '<tr>'
            . '<th align="left" style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#52525b;">Product</th>'
            . '<th style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#52525b;">Qty</th>'
            . '<th align="right" style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#52525b;">Unit price</th>'
            . '<th align="right" style="padding:8px;border-bottom:2px solid #17181B;font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#52525b;">Total</th>'
            . '</tr>' . $rows . '</table>';
    }

    private function addressBlock(Order $order): string
    {
        $lines = array_filter([
            e($order->customer_name),
            e($order->delivery_address),
            e(trim(($order->delivery_area ? $order->delivery_area . ', ' : '') . $order->delivery_city)),
            e($order->customer_phone),
        ]);
        return implode('<br>', $lines);
    }

    private function trackingLink(Order $order): string
    {
        return route('order.track', ['order_number' => $order->order_number]);
    }

    /** Variables for customer-facing order emails. */
    public function orderVars(Order $order): array
    {
        $order->loadMissing('items', 'user');
        $address = $this->addressBlock($order);
        $delivered = optional($order->updated_at)->format('d M Y') ?? now()->format('d M Y');

        return [
            'customer_name'           => $order->customer_name,
            'order_number'            => $order->order_number,
            'order_date'              => optional($order->created_at)->format('d M Y') ?? '',
            'billing_address'         => $address,
            'shipping_address'        => $address,
            'product_table'           => $this->productTable($order),
            'subtotal'                => $this->money($order->subtotal),
            'shipping_charge'         => $this->money($order->delivery_charge),
            'discount'                => $this->money($order->discount),
            'grand_total'             => $this->money($order->total),
            'order_total'             => $this->money($order->total),
            'payment_method'          => $this->paymentLabel($order->payment_method),
            'expected_delivery'       => $order->estimated_delivery_date
                ? $order->estimated_delivery_date->format('d M Y')
                : 'Within 3–5 business days',
            'tracking_link'           => $this->trackingLink($order),
            'support_email'           => $this->settings->brand()['support_email'],
            'courier_name'            => $order->courier_name ?: '—',
            'tracking_number'         => $order->tracking_number ?: '—',
            'tracking_url'            => $order->tracking_url ?: $this->trackingLink($order),
            'estimated_delivery_date' => $order->estimated_delivery_date
                ? $order->estimated_delivery_date->format('d M Y')
                : 'Within 3–5 business days',
            'delivered_date'          => $delivered,
            'cancel_reason'           => $order->cancel_reason ?: 'Not specified',
            'review_link'             => $order->user_id
                ? route('account.orders.show', $order->id)
                : $this->trackingLink($order),
            'reorder_link'            => route('shop.index'),
        ];
    }

    /** Variables for the admin new-order notification. */
    public function adminVars(Order $order): array
    {
        $order->loadMissing('items', 'user');

        return [
            'customer_name'  => $order->customer_name,
            'customer_phone' => $order->customer_phone ?: '—',
            'customer_email' => $order->customer_email ?: '—',
            'order_number'   => $order->order_number,
            'order_date'     => optional($order->created_at)->format('d M Y') ?? '',
            'order_total'    => $this->money($order->total),
            'grand_total'    => $this->money($order->total),
            'payment_method' => $this->paymentLabel($order->payment_method),
            'product_table'  => $this->productTable($order),
            'order_link'     => route('admin.orders.show', $order->id),
        ];
    }
}
