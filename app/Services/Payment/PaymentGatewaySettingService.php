<?php

namespace App\Services\Payment;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Crypt;

/**
 * Reads and writes payment gateway settings using the WebsiteSetting
 * key-value store with a `pg_` namespace prefix.
 * Sensitive fields are stored encrypted; logo paths are stored plain.
 */
class PaymentGatewaySettingService
{
    private const SENSITIVE = [
        'bkash'      => ['app_secret', 'password'],
        'nagad'      => ['private_key'],
        'sslcommerz' => ['store_password'],
    ];

    public function getAll(): array
    {
        return [
            'bkash'      => $this->getGateway('bkash'),
            'nagad'      => $this->getGateway('nagad'),
            'sslcommerz' => $this->getGateway('sslcommerz'),
        ];
    }

    public function getGateway(string $gateway): array
    {
        $result = [];
        foreach ($this->schemaFor($gateway) as $field) {
            $raw = WebsiteSetting::get("pg_{$gateway}_{$field}", '');
            $result[$field] = $this->isSensitive($gateway, $field)
                ? ($raw ? $this->tryDecrypt($raw) : '')
                : $raw;
        }
        return $result;
    }

    public function saveGateway(string $gateway, array $data): void
    {
        foreach ($data as $field => $value) {
            if ($value === null) continue;
            $storeValue = $this->isSensitive($gateway, $field)
                ? Crypt::encryptString((string) $value)
                : (string) $value;
            WebsiteSetting::set("pg_{$gateway}_{$field}", $storeValue);
        }
    }

    // ── Logo ──────────────────────────────────────────────────────────────────

    public function getLogoPath(string $gateway): string
    {
        return WebsiteSetting::get("pg_{$gateway}_logo_path", '');
    }

    public function getLogoUrl(string $gateway): ?string
    {
        $path = $this->getLogoPath($gateway);
        if (! $path) {
            return null;
        }
        // Media Library URLs (or absolute paths) are returned as-is; legacy
        // filenames still resolve from the old payment-logos folder.
        if (str_contains($path, '://') || str_starts_with($path, '/')) {
            return $path;
        }
        return asset("payment-logos/{$path}");
    }

    public function setLogoPath(string $gateway, string $filename): void
    {
        WebsiteSetting::set("pg_{$gateway}_logo_path", $filename);
    }

    public function clearLogoPath(string $gateway): void
    {
        WebsiteSetting::set("pg_{$gateway}_logo_path", '');
    }

    // ── Gateway state ─────────────────────────────────────────────────────────

    public function isEnabled(string $gateway): bool
    {
        return WebsiteSetting::get("pg_{$gateway}_enabled", '0') === '1';
    }

    /** Returns enabled gateways with their logo URLs */
    public function enabledGatewaysWithMeta(): array
    {
        $result = [];
        foreach (['bkash', 'nagad', 'sslcommerz'] as $gw) {
            if ($this->isEnabled($gw)) {
                $result[$gw] = ['logo_url' => $this->getLogoUrl($gw)];
            }
        }
        return $result;
    }

    public function enabledGateways(): array
    {
        return array_keys($this->enabledGatewaysWithMeta());
    }

    public function getMode(string $gateway): string
    {
        return WebsiteSetting::get("pg_{$gateway}_mode", 'sandbox');
    }

    public function isLive(string $gateway): bool
    {
        return $this->getMode($gateway) === 'live';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isSensitive(string $gateway, string $field): bool
    {
        return in_array($field, self::SENSITIVE[$gateway] ?? [], true);
    }

    private function tryDecrypt(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value;
        }
    }

    private function schemaFor(string $gateway): array
    {
        return match ($gateway) {
            'bkash'      => ['enabled', 'mode', 'app_key', 'app_secret', 'username', 'password', 'callback_url'],
            'nagad'      => ['enabled', 'mode', 'merchant_id', 'merchant_number', 'public_key', 'private_key', 'callback_url'],
            'sslcommerz' => ['enabled', 'mode', 'store_id', 'store_password', 'success_url', 'fail_url', 'cancel_url', 'ipn_url'],
            default      => [],
        };
    }
}
