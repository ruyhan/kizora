<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Nagad payment gateway integration.
 *
 * Docs: https://nagad.com.bd/merchant-api-doc/
 * Note: Nagad requires RSA encryption of sensitive payload data.
 */
class NagadService
{
    private const SANDBOX_BASE = 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs';
    private const LIVE_BASE    = 'https://api.mynagad.com/remote-payment-gateway-1.0/api/dfs';

    public function __construct(
        private readonly PaymentGatewaySettingService $settings
    ) {}

    /**
     * Initiate a Nagad payment and return the redirect URL.
     */
    public function initiatePayment(Order $order): array
    {
        $cfg = $this->settings->getGateway('nagad');

        $merchantId     = $cfg['merchant_id'];
        $datetime       = now()->format('YmdHis');
        $orderId        = $order->order_number;
        $callbackUrl    = $cfg['callback_url'] ?: route('payment.nagad.callback');

        // Step 1: Check merchant validity
        $sensitiveData = $this->encryptRSA([
            'merchantId' => $merchantId,
            'datetime'   => $datetime,
            'orderId'    => $orderId,
            'challenge'  => $this->randomString(),
        ], $cfg['public_key']);

        $checkBody = [
            'accountNumber' => $cfg['merchant_number'],
            'datetime'      => $datetime,
            'sensitiveData' => $sensitiveData,
            'signature'     => $this->signRSA($merchantId . $datetime . $orderId, $cfg['private_key']),
        ];

        $checkUrl  = $this->base() . '/check-merchant-validity/' . $merchantId . '/' . $orderId;
        $checkResp = Http::withHeaders(['X-KM-IP-V4' => request()->ip(), 'X-KM-Client-Type' => 'MOBILE_APP', 'X-KM-Api-Version' => 'v-0.2.0'])
            ->post($checkUrl, $checkBody);
        $checkData = $checkResp->json();

        if (empty($checkData['sensitiveData'])) {
            Log::warning('Nagad merchant check failed', ['response' => $checkData]);
            return ['success' => false, 'message' => 'Nagad merchant validation failed.'];
        }

        // Step 2: Create payment
        $decryptedChallenge = $this->decryptRSA($checkData['sensitiveData'], $cfg['private_key']);
        $challenge          = is_array($decryptedChallenge) ? ($decryptedChallenge['challenge'] ?? '') : '';

        $paymentSensitive = $this->encryptRSA([
            'merchantId'     => $merchantId,
            'orderId'        => $orderId,
            'challenge'      => $challenge,
            'amount'         => number_format($order->total, 2, '.', ''),
            'currencyCode'   => '050',
            'callbackURL'    => $callbackUrl,
        ], $cfg['public_key']);

        $createBody = [
            'datetime'      => $datetime,
            'sensitiveData' => $paymentSensitive,
            'signature'     => $this->signRSA($merchantId . $datetime . $orderId . number_format($order->total, 2, '.', ''), $cfg['private_key']),
            'merchantCallbackURL' => $callbackUrl,
        ];

        $createUrl  = $this->base() . '/initiate/merchant/' . $merchantId . '/payment/' . $orderId;
        $createResp = Http::withHeaders(['X-KM-IP-V4' => request()->ip(), 'X-KM-Client-Type' => 'MOBILE_APP', 'X-KM-Api-Version' => 'v-0.2.0'])
            ->post($createUrl, $createBody);
        $createData = $createResp->json();

        if (! empty($createData['callBackUrl'])) {
            PaymentTransaction::create([
                'order_id'           => $order->id,
                'gateway'            => 'nagad',
                'gateway_payment_id' => $createData['paymentReferenceId'] ?? null,
                'status'             => 'pending',
                'amount'             => $order->total,
                'gateway_response'   => $createData,
            ]);

            return ['success' => true, 'redirect_url' => $createData['callBackUrl']];
        }

        Log::warning('Nagad payment create failed', ['order' => $order->order_number, 'response' => $createData]);
        return ['success' => false, 'message' => 'Nagad payment initiation failed.'];
    }

    /**
     * Verify a completed Nagad payment.
     */
    public function verifyPayment(string $paymentRefId): array
    {
        $cfg = $this->settings->getGateway('nagad');
        $url = $this->base() . '/verify/payment/' . $paymentRefId;

        $response = Http::withHeaders([
            'X-KM-IP-V4'        => request()->ip(),
            'X-KM-Client-Type'  => 'MOBILE_APP',
            'X-KM-Api-Version'  => 'v-0.2.0',
        ])->get($url);

        return $response->json();
    }

    // ── Crypto helpers ────────────────────────────────────────────────────────

    private function encryptRSA(array $data, string $publicKey): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64) . "-----END PUBLIC KEY-----";
        openssl_public_encrypt(json_encode($data), $encrypted, $pem, OPENSSL_PKCS1_OAEP_PADDING);
        return base64_encode($encrypted);
    }

    private function decryptRSA(string $encryptedBase64, string $privateKey): mixed
    {
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($privateKey, 64) . "-----END RSA PRIVATE KEY-----";
        openssl_private_decrypt(base64_decode($encryptedBase64), $decrypted, $pem, OPENSSL_PKCS1_OAEP_PADDING);
        return json_decode($decrypted, true);
    }

    private function signRSA(string $data, string $privateKey): string
    {
        $pem = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($privateKey, 64) . "-----END RSA PRIVATE KEY-----";
        openssl_sign($data, $signature, $pem, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    private function randomString(int $length = 40): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    private function base(): string
    {
        return $this->settings->isLive('nagad') ? self::LIVE_BASE : self::SANDBOX_BASE;
    }
}
