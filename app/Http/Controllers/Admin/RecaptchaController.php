<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Recaptcha\RecaptchaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecaptchaController extends Controller
{
    public function __construct(private readonly RecaptchaService $recaptcha) {}

    public function index()
    {
        return Inertia::render('Admin/Recaptcha/Index', [
            'settings' => $this->recaptcha->adminConfig(),
            'forms'    => RecaptchaService::FORMS,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'         => 'required|boolean',
            'version'         => 'required|in:v2,v3',
            'site_key'        => 'nullable|string|max:255',
            'secret_key'      => 'nullable|string|max:255',
            'score_threshold' => 'required|numeric|min:0|max:1',
            'forms'           => 'array',
            'forms.*'         => 'boolean',
        ]);

        $payload = [
            'enabled'         => $data['enabled'] ? '1' : '0',
            'version'         => $data['version'],
            'site_key'        => $data['site_key'] ?? '',
            'score_threshold' => (string) $data['score_threshold'],
        ];

        // Only overwrite the secret if a new (non-masked) value was provided
        if (! empty($data['secret_key']) && $data['secret_key'] !== '••••••••') {
            $payload['secret_key'] = $data['secret_key'];
        }

        $this->recaptcha->save($payload);

        // Per-form flags
        foreach (RecaptchaService::FORMS as $form) {
            $on = ! empty($data['forms'][$form]);
            \App\Models\WebsiteSetting::set("recaptcha_form_{$form}", $on ? '1' : '0');
        }

        return back()->with('success', 'reCAPTCHA settings saved.');
    }
}
