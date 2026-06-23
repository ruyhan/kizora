<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Services\Recaptcha\RecaptchaService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(private readonly RecaptchaService $recaptcha) {}

    public function sendMessage(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10|max:2000',
        ]);

        if ($msg = $this->recaptcha->verifyRequest($request, 'contact')) {
            return back()->withErrors(['message' => $msg]);
        }

        ContactMessage::create($request->only('name', 'email', 'phone', 'subject', 'message'));

        return back()->with('success', 'Your message has been sent! We will get back to you soon.');
    }
}
