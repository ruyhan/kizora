<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Coupon;
use App\Support\DeliverySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private readonly DeliverySettings $delivery) {}

    private function getCartIdentifier(Request $request): array
    {
        if (Auth::check()) {
            return ['user_id' => Auth::id()];
        }
        return ['session_id' => $request->session()->getId()];
    }

    private function getCartItems(Request $request): array
    {
        $identifier = $this->getCartIdentifier($request);
        $items = Cart::with('product.category')
            ->where($identifier)
            ->get();

        $subtotal = 0;
        $formatted = $items->map(function ($item) use (&$subtotal) {
            $price = $item->product->final_price;
            $total = $price * $item->quantity;
            $subtotal += $total;
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'image' => $item->product->featured_image_url,
                'price' => $price,
                'quantity' => $item->quantity,
                'total' => $total,
                'stock' => $item->product->stock,
                'category' => $item->product->category->name,
                'variations' => $item->variations ?? [],
            ];
        });

        // Location is unknown on the cart page → estimate (free / minimum-met / Inside-Dhaka).
        $deliveryCharge = $this->delivery->chargeFor(null, $subtotal);
        $total = $subtotal + $deliveryCharge;

        return [
            'items' => $formatted,
            'subtotal' => $subtotal,
            'delivery_charge' => $deliveryCharge,
            'total' => $total,
            'count' => $items->sum('quantity'),
            'delivery' => $this->delivery->publicConfig(),
        ];
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Cart', [
            'cart' => $this->getCartItems($request),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id'           => 'required|exists:products,id',
            'quantity'             => 'required|integer|min:1|max:20',
            'variations'           => 'nullable|array',
            'variations.*.name'    => 'required_with:variations|string|max:60',
            'variations.*.value'   => 'nullable|string|max:120',
            'variations.*.label'   => 'nullable|string|max:120',
            'variations.*.type'    => 'nullable|string|max:20',
            'variations.*.image'   => 'nullable|string|max:1000',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return back()->with('error', 'Insufficient stock available.');
        }

        $variations = $request->input('variations') ?: null;
        $identifier = $this->getCartIdentifier($request);

        // Same product + identical variation selection collapses into one line;
        // a different selection becomes its own line.
        $cartItem = Cart::where($identifier)->where('product_id', $request->product_id)->get()
            ->first(fn ($i) => $this->variationSignature($i->variations) === $this->variationSignature($variations));

        if ($cartItem) {
            $newQty = $cartItem->quantity + $request->quantity;
            if ($newQty > $product->stock) {
                return back()->with('error', 'Cannot add more than available stock.');
            }
            $cartItem->update(['quantity' => $newQty]);
        } else {
            Cart::create(array_merge($identifier, [
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'variations' => $variations,
            ]));
        }

        return back();
    }

    /** Stable signature of a variation selection for matching cart lines. */
    private function variationSignature($variations): string
    {
        $v = $variations ?: [];
        usort($v, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        return json_encode(array_map(fn ($x) => [$x['name'] ?? '', $x['value'] ?? ''], $v));
    }

    public function update(Request $request, Cart $cart)
    {
        $request->validate(['quantity' => 'required|integer|min:1|max:20']);

        $identifier = $this->getCartIdentifier($request);
        $ownerKey = array_key_first($identifier);
        if ($cart->$ownerKey !== $identifier[$ownerKey]) {
            abort(403);
        }

        $cart->update(['quantity' => $request->quantity]);
        return back();
    }

    public function remove(Request $request, Cart $cart)
    {
        $identifier = $this->getCartIdentifier($request);
        $ownerKey = array_key_first($identifier);
        if ($cart->$ownerKey !== $identifier[$ownerKey]) {
            abort(403);
        }

        $cart->delete();
        return back();
    }

    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $cartData = $this->getCartItems($request);
        $coupon = Coupon::where('code', strtoupper($request->code))->first();

        if (!$coupon || !$coupon->isValid($cartData['subtotal'])) {
            return back()->withErrors(['coupon' => 'Invalid or expired coupon code.']);
        }

        session(['applied_coupon' => $coupon->code]);
        return back();
    }

    public function getCartJson(Request $request)
    {
        return response()->json($this->getCartItems($request));
    }
}
