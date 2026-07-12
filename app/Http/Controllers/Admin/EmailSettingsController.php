<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Services\Email\EmailSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class EmailSettingsController extends Controller
{
    public function __construct(private readonly EmailSettingService $settings) {}

    /** Common SMTP host/port presets for the driver dropdown. */
    private const PRESETS = [
        'smtp'     => ['label' => 'Custom SMTP', 'host' => '', 'port' => '587', 'encryption' => 'tls'],
        'gmail'    => ['label' => 'Gmail / Google Workspace', 'host' => 'smtp.gmail.com', 'port' => '587', 'encryption' => 'tls'],
        'sendgrid' => ['label' => 'SendGrid', 'host' => 'smtp.sendgrid.net', 'port' => '587', 'encryption' => 'tls'],
        'mailgun'  => ['label' => 'Mailgun', 'host' => 'smtp.mailgun.org', 'port' => '587', 'encryption' => 'tls'],
        'ses'      => ['label' => 'Amazon SES', 'host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => '587', 'encryption' => 'tls'],
        'mailtrap' => ['label' => 'Mailtrap (testing)', 'host' => 'sandbox.smtp.mailtrap.io', 'port' => '2525', 'encryption' => 'tls'],
    ];

    public function smtp()
    {
        return Inertia::render('Admin/Email/Smtp', [
            'settings' => $this->settings->smtp(),
            'presets'  => self::PRESETS,
        ]);
    }

    public function updateSmtp(Request $request)
    {
        $data = $request->validate([
            'enabled'          => 'boolean',
            'queue'            => 'boolean',
            'driver'           => 'required|string|max:40',
            'host'             => 'nullable|string|max:255',
            'port'             => 'nullable|string|max:10',
            'username'         => 'nullable|string|max:255',
            'password'         => 'nullable|string|max:255',
            'encryption'       => 'required|in:tls,ssl,none',
            'from_address'     => 'nullable|email',
            'from_name'        => 'nullable|string|max:255',
            'reply_to'         => 'nullable|email',
            'admin_recipients' => 'nullable|string|max:1000',
        ]);

        $this->settings->saveSmtp($data);

        return back()->with('success', 'Email settings saved.');
    }

    public function verifySmtp()
    {
        $result = $this->settings->verify();

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok'] ? 'SMTP connection successful.' : ('SMTP error: ' . $result['message']),
        );
    }

    public function testEmail(Request $request)
    {
        $request->validate(['to' => 'required|email']);

        $this->settings->apply(); // ensure latest saved config is active
        $brand = $this->settings->brand();
        $html = view('emails.layout', [
            'subject' => 'Test email from ' . $brand['company_name'],
            'brand'   => $brand,
            'content' => '<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">It works! 🎉</h1>'
                . '<p style="margin:0;color:#52525b;font-size:15px;line-height:1.6;">This is a test email confirming your email configuration is working correctly.</p>',
        ])->render();

        $log = EmailLog::create([
            'template_key' => 'test',
            'mailable'     => 'test',
            'recipient'    => $request->to,
            'subject'      => 'Test email',
            'status'       => 'queued',
        ]);

        try {
            Mail::html($html, fn ($m) => $m->to($request->to)->subject('Test email from ' . $brand['company_name']));
            $log->update(['status' => 'sent', 'sent_at' => now()]);
            return back()->with('success', 'Test email sent to ' . $request->to . '.');
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
    }

    public function brand()
    {
        // The form edits & re-saves the raw logo path — NOT the embedded data URI that
        // brand() returns for rendering (that would exceed validation on save).
        $brand = $this->settings->brand();
        $brand['logo'] = $this->settings->rawLogo();

        return Inertia::render('Admin/Email/Brand', [
            'brand' => $brand,
        ]);
    }

    public function updateBrand(Request $request)
    {
        // A base64 data URI (used only for rendering) must never be saved or validated as
        // the logo — swap it back to the raw stored path so the save always succeeds.
        if (str_starts_with((string) $request->input('logo'), 'data:')) {
            $request->merge(['logo' => $this->settings->rawLogo()]);
        }

        $data = $request->validate([
            'logo'              => 'nullable|string|max:1000',
            'banner'            => 'nullable|string|max:1000',
            'company_name'      => 'nullable|string|max:255',
            'address'           => 'nullable|string|max:500',
            'phone'             => 'nullable|string|max:60',
            'website'           => 'nullable|string|max:255',
            'support_email'     => 'nullable|email',
            'socials'           => 'array',
            'footer_disclaimer' => 'nullable|string|max:1000',
            'footer_copyright'  => 'nullable|string|max:255',
        ]);

        $this->settings->saveBrand($data);

        return back()->with('success', 'Email branding saved.');
    }

    /** Live preview of the brand chrome (header + footer) with sample content. */
    public function brandPreview(Request $request)
    {
        // Preview uses the posted (unsaved) brand values merged over saved defaults.
        $brand = array_merge($this->settings->brand(), array_filter($request->only([
            'logo', 'banner', 'company_name', 'address', 'phone', 'website',
            'support_email', 'footer_disclaimer', 'footer_copyright',
        ]), fn ($v) => $v !== null));
        if ($request->has('socials')) {
            $brand['socials'] = $request->input('socials') ?: [];
        }
        // Embed the logo (base64) so the preview matches what gets sent.
        $brand['logo'] = $this->settings->embedAsDataUri($brand['logo'] ?? '');

        $html = view('emails.layout', [
            'subject' => $brand['company_name'] ?? 'Preview',
            'brand'   => $brand,
            'content' => '<h1 style="margin:0 0 8px;font-size:22px;color:#17181B;">Hello there 👋</h1>'
                . '<p style="margin:0 0 18px;color:#52525b;font-size:15px;line-height:1.6;">This preview shows how your branding (logo, banner, footer details and colours) appears in every email your store sends.</p>'
                . '<p style="margin:0;"><a href="#" style="display:inline-block;background:#17181B;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:13px 24px;border-radius:10px;">Example button</a></p>',
        ])->render();

        return response()->json(['html' => $html]);
    }
}
