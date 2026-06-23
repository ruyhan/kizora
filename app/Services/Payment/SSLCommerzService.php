<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SSLCommerz payment gateway integration.
 *
 * Docs: https://developer.sslcommerz.com/doc/v4/
 */
class SSLCommerzService
{
    private const SANDBOX_INIT_URL  = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
    private const LIVE_INIT_URL     = 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
    private const SANDBOX_VALID_URL = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';
    private const LIVE_VALID_URL    = 'https://securepay.sslcommerz.com/validator/api/validationserverAPI.php';

    public function __construct(
        private readonly PaymentGatewaySettingService $settings
    ) {}

    /**
     * Initiate a payment session.
     * Returns the gateway page URL to redirect the user to.
     */
    public function initiatePayment(Order $order): array
    {
        $cfg = $this->settings->getGateway('sslcommerz');

        $params = [
            'store_id'         => $cfg['store_id'],
            'store_passwd'     => $cfg['store_password'],
            'total_amount'     => number_format($order->total, 2, '.', ''),
            'currency'         => 'BDT',
            'tran_id'          => $order->order_number,
            'success_url'      => $cfg['success_url'] ?: route('payment.sslcommerz.success'),
            'fail_url'         => $cfg['fail_url']    ?: route('payment.sslcommerz.fail'),
            'cancel_url'       => $cfg['cancel_url']  ?: route('payment.sslcommerz.cancel'),
            'ipn_url'          => $cfg['ipn_url']     ?: route('payment.sslcommerz.ipn'),
            'cus_name'         => $order->user?->name  ?? $order->guest_name,
            'cus_email'        => $order->user?->email ?? $order->guest_email,
            'cus_phone'        => $order->user?->phone ?? $order->guest_phone,
            'cus_add1'         => $order->delivery_address,
            'cus_city'         => $order->delivery_city,
            'cus_country'      => 'Bangladesh',
            'shipping_method'  => 'NO',
            'product_name'     => 'Lavender Bakery Order',
            'product_category' => 'Food',
            'product_profile'  => 'general',
            'emi_option'       => 0,
        ];

        $url = $this->isLive() ? self::LIVE_INIT_URL : self::SANDBOX_INIT_URL;

        $response = Http::asForm()->post($url, $params);
        $data = $response->json();

        if (($data['status'] ?? '') === 'SUCCESS' && ! empty($data['GatewayPageURL'])) {
            // Create pending transaction record
            PaymentTransaction::create([
                'order_id'           => $order->id,
                'gateway'            => 'sslcommerz',
                'gateway_payment_id' => $data['sessionkey'] ?? null,
                'status'             => 'pending',
                'amount'             => $order->total,
                'gateway_response'   => $data,
            ]);

            return ['success' => true, 'redirect_url' => $data['GatewayPageURL']];
        }

        Log::warning('SSLCommerz init failed', ['order' => $order->order_number, 'response' => $data]);
        return ['success' => false, 'message' => $data['failedreason'] ?? 'Payment initiation failed.'];
    }

    /**
     * Validate an IPN/success callback from SSLCommerz.
     */
    public function validatePayment(string $valId, string $storeId, string $storePassword): array
    {
        $url = $this->isLive() ? self::LIVE_VALID_URL : self::SANDBOX_VALID_URL;

        $response = Http::get($url, [
            'val_id'       => $valId,
            'store_id'     => $storeId,
            'store_passwd'  => $storePassword,
            'format'       => 'json',
        ]);

        return $response->json();
    }

    private function isLive(): bool
    {
        return $this->settings->isLive('sslcommerz');
    }
}
