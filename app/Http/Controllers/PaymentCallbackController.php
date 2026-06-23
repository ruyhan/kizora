<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payment\BkashService;
use App\Services\Payment\NagadService;
use App\Services\Payment\PaymentGatewaySettingService;
use App\Services\Payment\SSLCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private readonly PaymentGatewaySettingService $gatewaySettings,
        private readonly BkashService    $bkash,
        private readonly NagadService    $nagad,
        private readonly SSLCommerzService $sslcommerz
    ) {}

    // ── bKash ─────────────────────────────────────────────────────────────────

    public function bkashCallback(Request $request)
    {
        $paymentId = $request->get('paymentID');
        $status    = $request->get('status');

        if ($status === 'cancel') {
            return $this->cancelRedirect('Payment was cancelled.');
        }

        if ($status === 'failure' || ! $paymentId) {
            return $this->failRedirect('bKash payment failed.');
        }

        // Execute the payment (idempotent — bKash returns success even if called twice)
        $executeData = $this->bkash->executePayment($paymentId);

        if (($executeData['statusCode'] ?? '') !== '0000') {
            Log::warning('bKash execute failed', $executeData);
            return $this->failRedirect($executeData['statusMessage'] ?? 'Payment verification failed.');
        }

        return $this->finaliseTransaction(
            gateway: 'bkash',
            gatewayPaymentId: $paymentId,
            gatewayTxId: $executeData['trxID'] ?? null,
            amount: (float) ($executeData['amount'] ?? 0),
            rawResponse: $executeData
        );
    }

    // ── Nagad ─────────────────────────────────────────────────────────────────

    public function nagadCallback(Request $request)
    {
        $paymentRefId = $request->get('payment_ref_id');
        $status       = $request->get('status');

        if ($status === 'Cancelled' || ! $paymentRefId) {
            return $this->cancelRedirect('Payment was cancelled.');
        }

        $verifyData = $this->nagad->verifyPayment($paymentRefId);

        if (($verifyData['status'] ?? '') !== 'Success') {
            Log::warning('Nagad verify failed', $verifyData);
            return $this->failRedirect($verifyData['message'] ?? 'Payment verification failed.');
        }

        return $this->finaliseTransaction(
            gateway: 'nagad',
            gatewayPaymentId: $paymentRefId,
            gatewayTxId: $verifyData['issuerPaymentRefNo'] ?? null,
            amount: (float) ($verifyData['amount'] ?? 0),
            rawResponse: $verifyData
        );
    }

    // ── SSLCommerz ────────────────────────────────────────────────────────────

    public function sslcommerzSuccess(Request $request)
    {
        return $this->handleSslCommerz($request, 'success');
    }

    public function sslcommerzFail(Request $request)
    {
        return $this->failRedirect($request->get('error') ?? 'Payment failed.');
    }

    public function sslcommerzCancel(Request $request)
    {
        return $this->cancelRedirect('Payment was cancelled.');
    }

    public function sslcommerzIpn(Request $request)
    {
        // IPN: same as success but silent (no redirect)
        $this->handleSslCommerz($request, 'ipn');
        return response('IPN received', 200);
    }

    private function handleSslCommerz(Request $request, string $source): mixed
    {
        $valId         = $request->get('val_id');
        $tranId        = $request->get('tran_id'); // = order_number
        $cardAmount    = (float) $request->get('amount', 0);
        $storeId       = $request->get('store_id');
        $storePassword = $this->gatewaySettings->getGateway('sslcommerz')['store_password'] ?? '';

        // Server-side validation
        $validation = $this->sslcommerz->validatePayment($valId, $storeId, $storePassword);

        if (
            ($validation['status'] ?? '') !== 'VALID' &&
            ($validation['status'] ?? '') !== 'VALIDATED'
        ) {
            Log::warning('SSLCommerz validation failed', $validation);
            return $source === 'ipn' ? null : $this->failRedirect('Payment verification failed.');
        }

        $result = $this->finaliseTransaction(
            gateway: 'sslcommerz',
            gatewayPaymentId: $valId,
            gatewayTxId: $validation['bank_tran_id'] ?? null,
            amount: $cardAmount,
            rawResponse: $validation,
            orderNumber: $tranId
        );

        return $source === 'ipn' ? null : $result;
    }

    // ── Shared finalisation ───────────────────────────────────────────────────

    private function finaliseTransaction(
        string $gateway,
        string $gatewayPaymentId,
        ?string $gatewayTxId,
        float $amount,
        array $rawResponse,
        ?string $orderNumber = null
    ): mixed {
        return DB::transaction(function () use (
            $gateway, $gatewayPaymentId, $gatewayTxId, $amount, $rawResponse, $orderNumber
        ) {
            // Find the pending transaction record
            $tx = PaymentTransaction::where('gateway_payment_id', $gatewayPaymentId)
                ->where('gateway', $gateway)
                ->lockForUpdate()
                ->first();

            // Try to find via order_number if tx not found (SSLCommerz uses tran_id)
            if (! $tx && $orderNumber) {
                $order = Order::where('order_number', $orderNumber)->first();
                if ($order) {
                    $tx = PaymentTransaction::where('order_id', $order->id)
                        ->where('gateway', $gateway)
                        ->lockForUpdate()
                        ->first();
                }
            }

            if (! $tx) {
                Log::error("Payment transaction not found", compact('gateway', 'gatewayPaymentId'));
                return $this->failRedirect('Order not found.');
            }

            // Idempotency — already processed
            if ($tx->status === 'paid') {
                session(['last_order_number' => $tx->order->order_number]);
                return redirect()->route('order.success');
            }

            $order = $tx->order;

            // Mark transaction paid
            $tx->update([
                'gateway_transaction_id' => $gatewayTxId,
                'status'                 => 'paid',
                'amount'                 => $amount,
                'gateway_response'       => $rawResponse,
                'paid_at'                => now(),
            ]);

            // Update order status
            $order->update([
                'payment_status'         => 'paid',
                'payment_transaction_id' => $tx->id,
                'status'                 => 'confirmed',
            ]);

            session(['last_order_number' => $order->order_number]);
            return redirect()->route('order.success');
        });
    }

    // ── Redirect helpers ──────────────────────────────────────────────────────

    private function failRedirect(string $message): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('checkout.index')->withErrors(['payment' => $message]);
    }

    private function cancelRedirect(string $message): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('checkout.index')->with('info', $message);
    }
}
