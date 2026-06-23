<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * bKash Tokenized Checkout integration.
 *
 * Docs: https://developer.bka.sh/docs/tokenized-checkout
 * API version: v1.2.0-beta
 */
class BkashService
{
    private const SANDBOX_BASE = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout';
    private const LIVE_BASE    = 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout';

    public function __construct(
        private readonly PaymentGatewaySettingService $settings
    ) {}

    /**
     * Create a bKash payment session and return the bKash URL to redirect the user.
     */
    public function initiatePayment(Order $order): array
    {
        $cfg   = $this->settings->getGateway('bkash');
        $token = $this->grantToken($cfg);

        if (! $token) {
            return ['success' => false, 'message' => 'bKash authentication failed.'];
        }

        $callbackUrl = $cfg['callback_url'] ?: route('payment.bkash.callback');

        $payload = [
            'mode'                  => '0011',          // Tokenized Checkout
            'payerReference'        => $order->user?->phone ?? $order->guest_phone ?? 'customer',
            'callbackURL'           => $callbackUrl,
            'amount'                => number_format($order->total, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $order->order_number,
        ];

        $response = Http::withHeaders($this->authHeaders($cfg, $token))
            ->post($this->base() . '/create', $payload);

        $data = $response->json();

        if (($data['statusCode'] ?? '') === '0000' && ! empty($data['bkashURL'])) {
            PaymentTransaction::create([
                'order_id'           => $order->id,
                'gateway'            => 'bkash',
                'gateway_payment_id' => $data['paymentID'] ?? null,
                'status'             => 'pending',
                'amount'             => $order->total,
                'gateway_response'   => $data,
            ]);

            return ['success' => true, 'redirect_url' => $data['bkashURL']];
        }

        Log::warning('bKash create failed', ['order' => $order->order_number, 'response' => $data]);
        return ['success' => false, 'message' => $data['statusMessage'] ?? 'bKash payment initiation failed.'];
    }

    /**
     * Execute (complete) a bKash payment after the user approves on the bKash page.
     */
    public function executePayment(string $paymentId): array
    {
        $cfg   = $this->settings->getGateway('bkash');
        $token = $this->grantToken($cfg);

        if (! $token) {
            return ['success' => false, 'message' => 'bKash authentication failed.'];
        }

        $response = Http::withHeaders($this->authHeaders($cfg, $token))
            ->post($this->base() . '/execute', ['paymentID' => $paymentId]);

        return $response->json();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Obtain or refresh the bKash OAuth token (cached for 1 hour).
     */
    private function grantToken(array $cfg): ?string
    {
        $cacheKey = 'bkash_token_' . md5($cfg['app_key'] ?? '');

        return Cache::remember($cacheKey, 3500, function () use ($cfg) {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'username'     => $cfg['username'],
                'password'     => $cfg['password'],
            ])->post($this->base() . '/token/grant', [
                'app_key'    => $cfg['app_key'],
                'app_secret' => $cfg['app_secret'],
            ]);

            $data = $response->json();
            return $data['id_token'] ?? null;
        });
    }

    private function authHeaders(array $cfg, string $token): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'X-APP-Key'     => $cfg['app_key'],
        ];
    }

    private function base(): string
    {
        return $this->settings->isLive('bkash') ? self::LIVE_BASE : self::SANDBOX_BASE;
    }
}
