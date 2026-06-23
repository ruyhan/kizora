<?php

namespace App\Services\Recaptcha;

use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google reCAPTCHA settings + server-side verification.
 * Settings live in the WebsiteSetting key-value store under `recaptcha_*`.
 * The secret key is stored encrypted and never exposed to the frontend.
 */
class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** Forms that can be protected */
    public const FORMS = [
        'login', 'register', 'forgot_password', 'contact',
        'review', 'newsletter', 'checkout',
    ];

    // ── Settings access ────────────────────────────────────────────────────────

    public function isGloballyEnabled(): bool
    {
        return WebsiteSetting::get('recaptcha_enabled', '0') === '1';
    }

    public function version(): string
    {
        return WebsiteSetting::get('recaptcha_version', 'v3'); // v3 | v2
    }

    public function siteKey(): string
    {
        return WebsiteSetting::get('recaptcha_site_key', '');
    }

    public function secretKey(): string
    {
        $raw = WebsiteSetting::get('recaptcha_secret_key', '');
        if (! $raw) return '';
        try {
            return Crypt::decryptString($raw);
        } catch (\Exception) {
            return $raw;
        }
    }

    public function scoreThreshold(): float
    {
        return (float) WebsiteSetting::get('recaptcha_score_threshold', '0.5');
    }

    public function isFormEnabled(string $form): bool
    {
        return WebsiteSetting::get("recaptcha_form_{$form}", '0') === '1';
    }

    /** Keys present + globally on + this form on */
    public function isActiveFor(string $form): bool
    {
        return $this->isGloballyEnabled()
            && $this->siteKey() !== ''
            && $this->secretKey() !== ''
            && $this->isFormEnabled($form);
    }

    public function save(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value === null) continue;
            if ($key === 'secret_key') {
                WebsiteSetting::set('recaptcha_secret_key', Crypt::encryptString((string) $value));
                continue;
            }
            WebsiteSetting::set("recaptcha_{$key}", (string) $value);
        }
    }

    /** All settings for the admin page (secret masked by the controller) */
    public function adminConfig(): array
    {
        $forms = [];
        foreach (self::FORMS as $form) {
            $forms[$form] = $this->isFormEnabled($form);
        }

        return [
            'enabled'         => $this->isGloballyEnabled(),
            'version'         => $this->version(),
            'site_key'        => $this->siteKey(),
            'has_secret'      => $this->secretKey() !== '',
            'score_threshold' => $this->scoreThreshold(),
            'forms'           => $forms,
        ];
    }

    /** Safe config shared to every page (NO secret key) */
    public function publicConfig(): array
    {
        if (! $this->isGloballyEnabled() || ! $this->siteKey()) {
            return ['enabled' => false];
        }

        $forms = [];
        foreach (self::FORMS as $form) {
            $forms[$form] = $this->isFormEnabled($form);
        }

        return [
            'enabled'  => true,
            'version'  => $this->version(),
            'site_key' => $this->siteKey(),
            'forms'    => $forms,
        ];
    }

    // ── Verification ────────────────────────────────────────────────────────────

    /**
     * Verify a request's reCAPTCHA token for the given form.
     * Returns null when OK, or a user-facing error message when blocked.
     */
    public function verifyRequest(Request $request, string $form): ?string
    {
        // Not protected → allow
        if (! $this->isActiveFor($form)) {
            return null;
        }

        $token = $request->input('recaptcha_token');
        if (! $token) {
            return 'Verification failed. Please try again.';
        }

        try {
            $response = Http::asForm()->timeout(8)->post(self::VERIFY_URL, [
                'secret'   => $this->secretKey(),
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);
            $data = $response->json();
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verify request failed', ['form' => $form, 'error' => $e->getMessage()]);
            return 'Verification failed. Please try again.';
        }

        if (! ($data['success'] ?? false)) {
            return 'Verification failed. Please try again.';
        }

        // v3 — enforce score + action
        if ($this->version() === 'v3') {
            $score = (float) ($data['score'] ?? 0);
            if ($score < $this->scoreThreshold()) {
                Log::info('reCAPTCHA low score', ['form' => $form, 'score' => $score]);
                return 'Verification failed. Please try again.';
            }
            // Action match (best-effort; Google returns the action used)
            if (isset($data['action']) && $data['action'] !== $form) {
                return 'Verification failed. Please try again.';
            }
        }

        return null;
    }
}
