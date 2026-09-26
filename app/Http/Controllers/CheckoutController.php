<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PrivilegeMembership;
use App\Services\Payment\BkashService;
use App\Services\Payment\NagadService;
use App\Services\Payment\PaymentGatewaySettingService;
use App\Services\Payment\SSLCommerzService;
use App\Services\RewardPointService;
use App\Support\PointSettings;
use App\Support\PrivilegeCardSettings;
use App\Support\PrivilegeDiscount;
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
        private readonly PrivilegeDiscount $privilege,
        private readonly PrivilegeCardSettings $privileges,
        private readonly RewardPointService $points,
        private readonly PointSettings $pointSettings,
    ) {}

    /** @return array{0: ?Coupon, 1: ?string, 2: float} the session coupon, its code and its discount. */
    private function couponFor(float $subtotal): array
    {
        $couponCode = session('applied_coupon');
        if (! $couponCode) {
            return [null, null, 0.0];
        }
        $coupon = Coupon::where('code', $couponCode)->where('is_active', true)->first();
        $valid = $coupon && $coupon->isValid($subtotal);

        return [$valid ? $coupon : null, $couponCode, $valid ? (float) $coupon->calculateDiscount($subtotal) : 0.0];
    }

    private function cartSubtotal(Request $request): float
    {
        $identifier = Auth::check()
            ? ['user_id' => Auth::id()]
            : ['session_id' => $request->session()->getId()];

        return (float) Cart::with('product')->where($identifier)->get()
            ->sum(fn($item) => $item->product->priceForVariations($item->variations) * $item->quantity);
    }

    /**
     * Guest checks a Privilege card number before placing the order. Returns the
     * recalculated discount split so the summary matches what store() will charge.
     */
    public function verifyPrivilegeCard(Request $request)
    {
        $request->validate(['card_number' => 'required|string|max:30']);

        if (Auth::check() || ! $this->privileges->guestFieldEnabled()) {
            return response()->json(['valid' => false, 'message' => 'Privilege card discount is not available right now.'], 422);
        }

        $membership = $this->privilege->findApprovedCard($request->card_number);
        if (! $membership) {
            return response()->json(['valid' => false, 'message' => 'This card number is not valid or not active.'], 422);
        }

        $subtotal = $this->cartSubtotal($request);
        [, , $couponDiscount] = $this->couponFor($subtotal);
        $split = $this->privilege->combine($subtotal, $couponDiscount, true);

        return response()->json([
            'valid'           => true,
            'card_number'     => PrivilegeMembership::formatNumber($membership->card_number),
            'discount'        => $split['total'],
            'coupon_discount' => $split['coupon'],
            'member_discount' => $split['member'],
            'coupon_applied'  => $split['coupon_applied'],
            'message'         => $split['member'] > 0 || ! $split['coupon_applied']
                ? rtrim(rtrim(number_format($this->privilege->percent(), 2), '0'), '.') . '% Privilege Member discount applied.'
                : 'Card verified — your coupon already gives a bigger discount.',
        ]);
    }

    public function index(Request $request): Response|\Illuminate\Http\RedirectResponse
    {
        $identifier = Auth::check()
            ? ['user_id' => Auth::id()]
            : ['session_id' => $request->session()->getId()];

        $cartItems = Cart::with('product')->where($identifier)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $subtotal = $cartItems->sum(fn($item) => $item->product->priceForVariations($item->variations) * $item->quantity);
        $user = Auth::user();

        // Estimate from the user's saved area; the frontend recalculates live as
        // the city changes, and store() resolves the exact charge by location.
        $savedArea = $user?->delivery_area_id ? DeliveryArea::with('city')->find($user->delivery_area_id) : null;
        $deliveryCharge = $this->delivery->chargeFor($savedArea, $subtotal) ?? $this->delivery->estimate($subtotal);

        [, $couponCode, $couponDiscount] = $this->couponFor($subtotal);
        $membership = $this->privilege->membershipFor($user, null);
        $split = $this->privilege->combine($subtotal, $couponDiscount, (bool) $membership);
        $discount = $split['total'];

        $total = $subtotal + $deliveryCharge - $discount;

        return Inertia::render('Checkout', [
            'cart' => [
                'items' => $cartItems->map(function ($item) {
                    $unit = $item->product->priceForVariations($item->variations);
                    return [
                        'id' => $item->id,
                        'name' => $item->product->name,
                        'image' => $item->product->featured_image_url,
                        'price' => $unit,
                        'quantity' => $item->quantity,
                        'total' => $unit * $item->quantity,
                        'variations' => $item->variations ?? [],
                    ];
                }),
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => $discount,
                'coupon_discount' => $split['coupon'],
                'member_discount' => $split['member'],
                'coupon_applied'  => $split['coupon_applied'],
                'total' => $total,
                'coupon_code' => $couponCode,
            ],
            // Privilege Member discount: auto for approved members, card-number field for guests.
            'privilege' => [
                'percent'     => $this->privilege->percent(),
                'member_card' => $membership ? PrivilegeMembership::formatNumber($membership->card_number) : null,
                'guest_field' => ! $user && $this->privileges->guestFieldEnabled(),
            ],
            // Reward points — logged-in customers without an approved Privilege Card.
            'points' => $this->points->eligible($user) ? array_merge($this->pointSettings->publicRules(), [
                'balance' => (int) $user->reward_points,
                'max'     => $this->points->maxRedeemable($user, $subtotal - $discount),
            ]) : null,
            // Autofill from the user's saved profile delivery details
            'user'             => $user ? [
                'name'             => $user->name,
                'email'            => $user->email,
                'phone'            => $user->phone,
                'delivery_area_id' => $user->delivery_area_id,
                'delivery_address' => $user->delivery_address,
            ] : null,
            // Saved address book — pick one to fill the form instantly
            'savedAddresses'   => $user ? $user->addresses()->orderByDesc('is_default')->latest()->get([
                'id', 'label', 'name', 'phone', 'address_line', 'city', 'postal_code', 'is_default',
            ]) : [],
            // Checkout shows only enabled (active) cities + their areas
            'deliveryCities'   => DeliveryCity::active()->orderBy('name')->get(['id', 'name', 'is_active', 'delivery_charge']),
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
            'phone'            => ['required', new \App\Rules\BdPhone],
            'address'          => 'required|string',
            'delivery_area_id' => ['required', Rule::exists('delivery_areas', 'id')],
            'payment_method'   => 'required|in:' . implode(',', $allowedMethods),
            'notes'            => 'nullable|string|max:500',
            'save_address'     => 'nullable|boolean',
            'privilege_card'   => 'nullable|string|max:30',
            'redeem_points'    => 'nullable|integer|min:0',
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

        // Prevent overselling — re-check stock at order time (incl. per-option stock).
        foreach ($cartItems as $item) {
            if ($item->quantity > $item->product->availableStockFor($item->variations)) {
                return redirect()->route('cart.index')->with('error', "Insufficient stock for {$item->product->name}.");
            }
        }

        $subtotal = $cartItems->sum(fn($item) => $item->product->priceForVariations($item->variations) * $item->quantity);
        // Exact charge based on the chosen delivery location + subtotal (free delivery overrides).
        // null = the selected city has no charge configured → block checkout with the admin message.
        $deliveryCharge = $this->delivery->chargeFor($area, $subtotal);
        if ($deliveryCharge === null) {
            return back()->withErrors(['delivery_area_id' => $this->delivery->noChargeMessage()]);
        }
        [$coupon, $couponCode, $couponDiscount] = $this->couponFor($subtotal);

        // Privilege discount — members automatically; guests via a verified card number.
        $membership = $this->privilege->membershipFor(Auth::user(), $request->privilege_card);
        if (! Auth::check() && filled($request->privilege_card) && ! $membership && $this->privileges->guestFieldEnabled()) {
            return back()->withErrors(['privilege_card' => 'This card number is not valid or not active.']);
        }
        $split = $this->privilege->combine($subtotal, $couponDiscount, (bool) $membership);
        if ($coupon && ! $split['coupon_applied']) {
            // Member discount was the better deal — the coupon is not used on this order.
            [$coupon, $couponCode] = [null, null];
        }
        $discount = $split['total'];
        $memberDiscount = $split['member'];
        $cardNumber = $memberDiscount > 0 ? $membership->card_number : null;

        // Reward points (customers without a Privilege Card) — on what's left after the other discounts.
        $redeemPoints = (int) $request->input('redeem_points', 0);
        $pointsDiscount = 0.0;
        if ($redeemPoints > 0) {
            if (! $this->points->eligible(Auth::user())) {
                return back()->withErrors(['redeem_points' => 'Reward points are not available for this account.']);
            }
            $pointsDiscount = $this->points->validateRedeem(Auth::user(), $redeemPoints, $subtotal - $discount);
            $discount += $pointsDiscount;
        }

        $total = $subtotal + $deliveryCharge - $discount;

        DB::transaction(function () use ($request, $cartItems, $subtotal, $deliveryCharge, $discount, $memberDiscount, $cardNumber, $redeemPoints, $pointsDiscount, $total, $coupon, $couponCode, $identifier, $area) {
            $order = Order::create([
                'user_id' => Auth::id(),
                // Snapshot the contact details entered at checkout (even when logged
                // in) so edits on the checkout form are captured on the order.
                'guest_name' => $request->name,
                'guest_email' => $request->email,
                'guest_phone' => $request->phone,
                'delivery_address' => $request->address,
                // Snapshot the area/city/postal used at order time
                'delivery_city' => $area->city->name,
                'delivery_area' => $area->name,
                'delivery_postal_code' => $area->postal_code,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => $discount,
                'member_discount' => $memberDiscount,
                'privilege_card_number' => $cardNumber,
                'points_redeemed' => $redeemPoints,
                'points_discount' => $pointsDiscount,
                'total' => $total,
                'coupon_code' => $couponCode,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            if ($redeemPoints > 0) {
                $this->points->redeem(Auth::user(), $order, $redeemPoints); // re-checks the balance under a lock
            }

            foreach ($cartItems as $item) {
                $unitPrice = $item->product->priceForVariations($item->variations);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $item->product->featured_image_url,
                    'price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'total' => $unitPrice * $item->quantity,
                    'variations' => $item->variations ?? null,
                ]);
                $item->product->decrement('stock', $item->quantity); // sync inventory
                $item->product->decrementVariationStock($item->variations, $item->quantity);
            }

            \App\Models\Invoice::forOrder($order); // generate invoice for the order

            if ($coupon) {
                $coupon->increment('used_count');
            }

            Cart::where($identifier)->delete();
            session()->forget('applied_coupon');

            session(['last_order_id' => $order->id, 'last_order_number' => $order->order_number]);
        });

        // Optionally save the delivery details to the user's address book
        if (Auth::check() && $request->boolean('save_address')) {
            $exists = Auth::user()->addresses()
                ->where('address_line', $request->address)
                ->where('phone', $request->phone)
                ->exists();
            if (! $exists) {
                Auth::user()->addresses()->create([
                    'label'        => 'Address ' . (Auth::user()->addresses()->count() + 1),
                    'name'         => $request->name,
                    'phone'        => $request->phone,
                    'address_line' => $request->address,
                    'city'         => $area->city->name,
                    'postal_code'  => $area->postal_code,
                    'is_default'   => ! Auth::user()->addresses()->exists(),
                ]);
            }
        }

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
            'content' => app(\App\Support\PageContent::class)->get('order_success'),
        ]);
    }
}
