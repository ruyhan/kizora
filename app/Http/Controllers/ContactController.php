<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\Email\EmailService;
use App\Services\Email\EmailSettingService;
use App\Services\Recaptcha\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function __construct(private readonly RecaptchaService $recaptcha) {}

    public function sendMessage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => ['nullable', new \App\Rules\BdPhone],
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10|max:2000',
        ]);

        if ($msg = $this->recaptcha->verifyRequest($request, 'contact')) {
            return back()->withErrors(['message' => $msg]);
        }

        $contact = ContactMessage::create($request->only('name', 'email', 'phone', 'subject', 'message'));

        // Notify admins by email — escaped, logged, and never blocks/breaks the form.
        try {
            $emails   = app(EmailService::class);
            $settings = app(EmailSettingService::class);
            $vars = [
                'contact_name'    => e($contact->name),
                'contact_email'   => e($contact->email),
                'contact_phone'   => e($contact->phone ?: '—'),
                'contact_subject' => e($contact->subject ?: 'No subject'),
                'contact_message' => e($contact->message),
            ];
            foreach ($settings->adminRecipients() as $adminEmail) {
                $emails->send('contact_received_admin', $adminEmail, $vars);
            }
        } catch (\Throwable $e) {
            Log::warning('Contact notification email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Your message has been sent! We will get back to you soon.');
    }
}
