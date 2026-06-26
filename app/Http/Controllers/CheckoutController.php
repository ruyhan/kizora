<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payment\BkashService;
use App\Services\Payment\NagadService;
use App\Services\Payment\PaymentGatewaySettingService;
use App\Services\Payment\SSLCommerzService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentGatewaySettingService $gatewaySettings,
        private readonly BkashService     $bkash,
        private readonly NagadService     $nagad,
        private readonly SSLCommerzService $sslcommerz,
        private readonly \App\Services\Recaptcha\RecaptchaService $recaptcha,
        private readonly \App\Support\DeliverySettings $delivery,
    ) {}

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $identifier = Auth::check()
            ? ['user_id' => Auth::id()]
            : ['session_id' => $request->session()->getId()];

        $cartItems = Cart::with('product')->where($identifier)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum(fn($item) => $item->product->final_price * $item->quantity);
        $user = Auth::user();

        // Estimate from the user's saved area; the frontend recalculates live as
        // the city changes, and store() resolves the exact charge by location.
        $savedArea = $user?->delivery_area_id ? DeliveryArea::with('city')->find($user->delivery_area_id) : null;
        $deliveryCharge = $this->delivery->chargeFor($savedArea, $subtotal);
        $discount = 0;

        $couponCode = session('applied_coupon');
        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
            if ($coupon && $coupon->isValid($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $total = $subtotal + $deliveryCharge - $discount;

        return Inertia::render('Checkout', [
            'cart' => [
                'items' => $cartItems->map(fn($item) => [
                    'id' => $item->id,
                    'name' => $item->product->name,
                    'image' => $item->product->featured_image_url,
                    'price' => $item->product->final_price,
                    'quantity' => $item->quantity,
                    'total' => $item->product->final_price * $item->quantity,
                    'variations' => $item->variations ?? [],
                ]),
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => $discount,
                'total' => $total,
                'coupon_code' => $couponCode,
            ],
            // Autofill from the user's saved profile delivery details
            'user'             => $user ? [
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'delivery_area_id' => $user->delivery_area_id,
                'delivery_address' => $user->delivery_address,
            ] : null,
            // Checkout shows only enabled (active) cities + their areas
            'deliveryCities'   => DeliveryCity::active()->orderBy('name')->get(['id', 'name', 'is_active']),
            'deliveryAreas'    => DeliveryArea::whereHas('city', fn($q) => $q->where('is_active', true))
                ->orderBy('name')->get(['id', 'delivery_city_id', 'name', 'postal_code']),
            'enabledGateways'  => $this->gatewaySettings->enabledGatewaysWithMeta(),
        ]);
    }

    public function store(Request $request)
    {
        $allowedMethods = array_merge(['cod'], array_keys($this->gatewaySettings->enabledGatewaysWithMeta()));

        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email',
            'phone'            => 'required|string|max:20',
            'address'          => 'required|string',
            'delivery_area_id' => ['required', Rule::exists('delivery_areas', 'id')],
            'payment_method'   => 'required|in:' . implode(',', $allowedMethods),
            'notes'            => 'nullable|string|max:500',
        ], [
            'delivery_area_id.required' => 'Please select your delivery city and area.',
        ]);

        if ($msg = $this->recaptcha->verifyRequest($request, 'checkout')) {
            return back()->withErrors(['payment' => $msg]);
        }

        $area = DeliveryArea::with('city')->find($request->delivery_area_id);

        // Block order if the selected city is not currently active for delivery
        if (! $area || ! $area->city || ! $area->city->is_active) {
            return back()->withErrors([
                'delivery_area_id' => 'Product is not available in your city yet. Please select another delivery city.',
            ]);
        }

        $identifier = Auth::check()
            ? ['user_id' => Auth::id()]
            : ['session_id' => $request->session()->getId()];

        $cartItems = Cart::with('product')->where($identifier)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        // Prevent overselling — re-check stock at order time.
        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->stock) {
                return redirect()->route('cart.index')->with('error', "Insufficient stock for {$item->product->name}.");
            }
        }

        $subtotal = $cartItems->sum(fn($item) => $item->product->final_price * $item->quantity);
        // Exact charge based on the chosen delivery location + subtotal (free delivery overrides).
        $deliveryCharge = $this->delivery->chargeFor($area, $subtotal);
        $discount = 0;

        $couponCode = session('applied_coupon');
        $coupon = null;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
            if ($coupon && $coupon->isValid($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $total = $subtotal + $deliveryCharge - $discount;

        DB::transaction(function () use ($request, $cartItems, $subtotal, $deliveryCharge, $discount, $total, $coupon, $couponCode, $identifier, $area) {
            $order = Order::create([
                'user_id' => Auth::id(),
                'guest_name' => Auth::check() ? null : $request->name,
                'guest_email' => Auth::check() ? null : $request->email,
                'guest_phone' => Auth::check() ? null : $request->phone,
                'delivery_address' => $request->address,
                // Snapshot the area/city/postal used at order time
                'delivery_city' => $area->city->name,
                'delivery_area' => $area->name,
                'delivery_postal_code' => $area->postal_code,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => $discount,
                'total' => $total,
                'coupon_code' => $couponCode,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $item->product->featured_image_url,
                    'price' => $item->product->final_price,
                    'quantity' => $item->quantity,
                    'total' => $item->product->final_price * $item->quantity,
                    'variations' => $item->variations ?? null,
                ]);
                $item->product->decrement('stock', $item->quantity); // sync inventory
            }

            \App\Models\Invoice::forOrder($order); // generate invoice for the order

            if ($coupon) {
                $coupon->increment('used_count');
            }

            Cart::where($identifier)->delete();
            session()->forget('applied_coupon');

            session(['last_order_id' => $order->id, 'last_order_number' => $order->order_number]);
        });

        $order = Order::where('order_number', session('last_order_number'))->first();

        // Transactional emails — customer confirmation + admin notification.
        // Queued + logged; never block or break checkout if mail fails.
        if ($order) {
            try {
                $emails   = app(\App\Services\Email\EmailService::class);
                $settings = app(\App\Services\Email\EmailSettingService::class);
                $emails->send('order_placed_customer', $order->customer_email, $emails->orderVars($order), $order->id);
                foreach ($settings->adminRecipients() as $adminEmail) {
                    $emails->send('order_received_admin', $adminEmail, $emails->adminVars($order), $order->id);
                }
            } catch (\Throwable $e) {
                Log::warning('Order email dispatch failed: ' . $e->getMessage());
            }
        }

        // COD: go straight to success
        if ($request->payment_method === 'cod') {
            return redirect()->route('order.success')->with('success', 'Order placed successfully!');
        }

        // Gateway payment: initiate and redirect
        $result = match ($request->payment_method) {
            'bkash'      => $this->bkash->initiatePayment($order),
            'nagad'      => $this->nagad->initiatePayment($order),
            'sslcommerz' => $this->sslcommerz->initiatePayment($order),
        };

        if (! $result['success']) {
            Log::error('Payment initiation failed', ['gateway' => $request->payment_method, 'message' => $result['message']]);
            return back()->withErrors(['payment' => $result['message']]);
        }

        // Redirect to external payment page via Inertia::location
        return Inertia::location($result['redirect_url']);
    }

    public function success(): Response
    {
        $orderNumber = session('last_order_number');
        if (!$orderNumber) {
            return redirect()->route('home');
        }

        return Inertia::render('OrderSuccess', [
            'orderNumber' => $orderNumber,
        ]);
    }
}
