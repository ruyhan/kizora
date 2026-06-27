<?php

namespace App\Services\Email;

use App\Models\WebsiteSetting;
use App\Support\PageContent;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Reads/writes the admin-managed mail configuration (SMTP + global email
 * branding) from the cached WebsiteSetting key/value store, applies it over
 * config('mail') at runtime, and verifies SMTP credentials.
 */
class EmailSettingService
{
    public const MASK = '••••••••';

    /** Per-request cache of url → data-URI conversions. */
    private array $uriCache = [];

    /** @return array<string,mixed> */
    public function smtp(bool $maskSecret = true): array
    {
        $pass = $this->readPassword();

        return [
            'enabled'          => WebsiteSetting::get('mail_enabled', '0') === '1',
            'queue'            => WebsiteSetting::get('mail_queue', '0') === '1',
            'driver'           => WebsiteSetting::get('mail_driver', 'smtp'),
            'host'             => (string) WebsiteSetting::get('mail_host', ''),
            'port'             => (string) WebsiteSetting::get('mail_port', '587'),
            'username'         => (string) WebsiteSetting::get('mail_username', ''),
            'password'         => $maskSecret ? ($pass !== '' ? self::MASK : '') : $pass,
            'encryption'       => (string) WebsiteSetting::get('mail_encryption', 'tls'),
            'from_address'     => (string) WebsiteSetting::get('mail_from_address', ''),
            'from_name'        => (string) WebsiteSetting::get('mail_from_name', ''),
            'reply_to'         => (string) WebsiteSetting::get('mail_reply_to', ''),
            'admin_recipients' => (string) WebsiteSetting::get('mail_admin_recipients', ''),
        ];
    }

    public function saveSmtp(array $data): void
    {
        $map = [
            'mail_enabled'          => ! empty($data['enabled']) ? '1' : '0',
            'mail_queue'            => ! empty($data['queue']) ? '1' : '0',
            'mail_driver'           => $data['driver'] ?? 'smtp',
            'mail_host'             => $data['host'] ?? '',
            'mail_port'             => (string) ($data['port'] ?? '587'),
            'mail_username'         => $data['username'] ?? '',
            'mail_encryption'       => $data['encryption'] ?? 'tls',
            'mail_from_address'     => $data['from_address'] ?? '',
            'mail_from_name'        => $data['from_name'] ?? '',
            'mail_reply_to'         => $data['reply_to'] ?? '',
            'mail_admin_recipients' => $data['admin_recipients'] ?? '',
        ];
        foreach ($map as $k => $v) {
            WebsiteSetting::set($k, $v);
        }

        // Only overwrite the password when a new, non-masked value is provided.
        if (! empty($data['password']) && $data['password'] !== self::MASK) {
            WebsiteSetting::set('mail_password', Crypt::encryptString($data['password']));
        }
    }

    /** Decrypt the stored SMTP password; falls back to raw for legacy plaintext values. */
    private function readPassword(): string
    {
        $raw = (string) WebsiteSetting::get('mail_password', '');
        if ($raw === '') {
            return '';
        }
        try {
            return Crypt::decryptString($raw);
        } catch (\Throwable) {
            return $raw; // legacy plaintext stored before encryption was added
        }
    }

    public function enabled(): bool
    {
        return WebsiteSetting::get('mail_enabled', '0') === '1';
    }

    /** Background sending (requires a running queue worker). Off = send instantly. */
    public function queueEnabled(): bool
    {
        return WebsiteSetting::get('mail_queue', '0') === '1';
    }

    /** Override the runtime mail config from saved settings. Called on boot. */
    public function apply(): void
    {
        $s = $this->smtp(false);
        if (! $s['enabled']) {
            return; // master switch off → leave the .env default mailer alone
        }

        if ($s['from_address']) {
            config(['mail.from.address' => $s['from_address']]);
        }
        if ($s['from_name']) {
            config(['mail.from.name' => $s['from_name']]);
        }

        // No SMTP host configured yet → keep the env default mailer (e.g. `log`),
        // so sending still works for local testing while branding/templates are set up.
        if (empty($s['host'])) {
            return;
        }

        config([
            'mail.default'                 => 'smtp',
            'mail.mailers.smtp.scheme'     => $s['encryption'] === 'ssl' ? 'smtps' : 'smtp',
            'mail.mailers.smtp.url'        => null,
            'mail.mailers.smtp.host'       => $s['host'],
            'mail.mailers.smtp.port'       => (int) $s['port'],
            'mail.mailers.smtp.username'   => $s['username'] ?: null,
            'mail.mailers.smtp.password'   => $s['password'] ?: null,
        ]);
    }

    /**
     * Test the SMTP connection with the saved (or provided) credentials.
     * @return array{ok:bool,message:string}
     */
    public function verify(?array $override = null): array
    {
        $s = $override ?? $this->smtp(false);

        if (empty($s['host'])) {
            return ['ok' => false, 'message' => 'No SMTP host configured.'];
        }

        try {
            $tls = match ($s['encryption'] ?? 'tls') {
                'ssl'   => true,   // implicit TLS (smtps)
                'none'  => false,
                default => null,   // STARTTLS auto-negotiated
            };
            $transport = new EsmtpTransport($s['host'], (int) ($s['port'] ?: 587), $tls);
            if (! empty($s['username'])) {
                $transport->setUsername($s['username']);
            }
            if (! empty($s['password'])) {
                $transport->setPassword($s['password']);
            }
            $transport->start();
            $transport->stop();

            return ['ok' => true, 'message' => 'Connection successful.'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /** Recipients for admin order-notification emails. */
    public function adminRecipients(): array
    {
        $raw = (string) WebsiteSetting::get('mail_admin_recipients', '');
        $list = array_filter(array_map('trim', explode(',', $raw)));
        if (! empty($list)) {
            return array_values($list);
        }
        $brand = $this->brand();
        $fallback = $brand['support_email'] ?: $this->smtp(false)['from_address'];

        return $fallback ? [$fallback] : [];
    }

    /** Global email branding (with sensible fallbacks to the site settings). */
    public function brand(): array
    {
        $content = app(PageContent::class)->get('settings');
        $socials = json_decode((string) WebsiteSetting::get('email_socials', ''), true) ?: [];

        // Fallback to the site's social links (platform => url) when no email-specific override is set.
        $contentSocials = [];
        foreach ((array) ($content['social_links'] ?? []) as $s) {
            if (! empty($s['platform']) && ! empty($s['url'])) {
                $contentSocials[$s['platform']] = $s['url'];
            }
        }

        return [
            'logo'              => $this->embedAsDataUri(WebsiteSetting::get('email_logo', '') ?: ($content['logo_light'] ?? '') ?: ($content['logo'] ?? '')),
            'banner'            => (string) WebsiteSetting::get('email_banner', ''),
            'company_name'      => WebsiteSetting::get('email_company_name', '') ?: ($content['site_name'] ?? 'Kizora'),
            'address'           => WebsiteSetting::get('email_company_address', '') ?: ($content['address'] ?? ''),
            'phone'             => WebsiteSetting::get('email_phone', '') ?: ($content['phone_1'] ?? ''),
            'website'           => WebsiteSetting::get('email_website', '') ?: config('app.url'),
            'support_email'     => WebsiteSetting::get('email_support_email', '') ?: ($content['email'] ?? ''),
            'socials'           => ! empty($socials) ? $socials : $contentSocials,
            'footer_disclaimer' => WebsiteSetting::get('email_footer_disclaimer', '')
                ?: 'You are receiving this email because you placed an order or have an account with us.',
            'footer_copyright'  => WebsiteSetting::get('email_footer_copyright', '')
                ?: '© ' . date('Y') . ' ' . ($content['site_name'] ?? 'Kizora') . '. All rights reserved.',
        ];
    }

    public function saveBrand(array $data): void
    {
        $map = [
            'email_logo'              => $data['logo'] ?? '',
            'email_banner'            => $data['banner'] ?? '',
            'email_company_name'      => $data['company_name'] ?? '',
            'email_company_address'   => $data['address'] ?? '',
            'email_phone'             => $data['phone'] ?? '',
            'email_website'           => $data['website'] ?? '',
            'email_support_email'     => $data['support_email'] ?? '',
            'email_socials'           => json_encode($data['socials'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'email_footer_disclaimer' => $data['footer_disclaimer'] ?? '',
            'email_footer_copyright'  => $data['footer_copyright'] ?? '',
        ];
        foreach ($map as $k => $v) {
            WebsiteSetting::set($k, $v);
        }
    }

    /**
     * Convert a locally-stored image URL/path into a base64 data URI so the logo
     * is embedded in the email and always renders, even when the source URL
     * (e.g. a local/dev domain) isn't reachable by the recipient. Remote URLs,
     * missing files, or oversized images (>256 KB) are returned unchanged.
     */
    public function embedAsDataUri(?string $url): ?string
    {
        if (! $url || str_starts_with($url, 'data:')) {
            return $url;
        }
        if (array_key_exists($url, $this->uriCache)) {
            return $this->uriCache[$url];
        }

        $file = $this->resolveLocalFile($url);
        if (! $file) {
            return $this->uriCache[$url] = $url; // remote or not found → keep the URL
        }

        $data = @file_get_contents($file);
        if ($data === false || strlen($data) > 256 * 1024) {
            return $this->uriCache[$url] = $url;
        }

        $mime = function_exists('mime_content_type') ? (mime_content_type($file) ?: 'image/png') : 'image/png';

        return $this->uriCache[$url] = 'data:' . $mime . ';base64,' . base64_encode($data);
    }

    /** Resolved brand-logo URL (email logo → light logo → main logo). */
    public function logoUrl(): string
    {
        $content = app(PageContent::class)->get('settings');

        return WebsiteSetting::get('email_logo', '') ?: ($content['logo_light'] ?? '') ?: ($content['logo'] ?? '');
    }

    /** Local filesystem path of the brand logo, for CID inline embedding (null if remote/missing). */
    public function logoFile(): ?string
    {
        return $this->resolveLocalFile($this->logoUrl());
    }

    /** Map a /storage or public URL to its file on disk (null if remote/missing). */
    private function resolveLocalFile(?string $url): ?string
    {
        if (! $url || str_starts_with($url, 'data:')) {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        if (($pos = strpos($path, '/storage/')) !== false) {
            $candidate = storage_path('app/public/' . substr($path, $pos + strlen('/storage/')));
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        $candidate = public_path(ltrim($path, '/'));

        return is_file($candidate) ? $candidate : null;
    }
}
