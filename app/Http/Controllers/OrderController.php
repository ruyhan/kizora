<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $reviewed = Review::where('user_id', Auth::id())->pluck('product_id')->flip();

        $orders = Order::with('items.product:id,slug')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10)
            ->through(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'status' => $o->status,
                'status_label' => $o->status_label,
                'status_color' => $o->status_color,
                'total' => $o->total,
                'payment_method' => $o->payment_method,
                'items_count' => $o->items->count(),
                'created_at' => $o->created_at->format('d M Y'),
                'can_review' => $o->status === 'completed',
                'items' => $o->items->map(fn($i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'name' => $i->product_name,
                    'image' => $i->product_image,
                    'slug' => $i->product?->slug,
                    'reviewed' => isset($reviewed[$i->product_id]),
                ])->values(),
            ]);

        return Inertia::render('Account/Orders', ['orders' => $orders]);
    }

    public function show(Order $order): Response
    {
        if ($order->user_id !== Auth::id()) abort(403);

        $order->load('items.product:id,slug');

        $reviewed = Review::where('user_id', Auth::id())
            ->whereIn('product_id', $order->items->pluck('product_id')->filter())
            ->pluck('product_id')->flip();

        return Inertia::render('Account/OrderDetail', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'status_color' => $order->status_color,
                'subtotal' => $order->subtotal,
                'delivery_charge' => $order->delivery_charge,
                'discount' => $order->discount,
                'total' => $order->total,
                'coupon_code' => $order->coupon_code,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'courier_name' => $order->courier_name,
                'tracking_number' => $order->tracking_number,
                'delivery_address' => $order->delivery_address,
                'delivery_city' => $order->delivery_city,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('d M Y, h:i A'),
                'can_review' => $order->status === 'completed',
                'items' => $order->items->map(fn($i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'product_name' => $i->product_name,
                    'product_image' => $i->product_image,
                    'slug' => $i->product?->slug,
                    'reviewed' => isset($reviewed[$i->product_id]),
                    'price' => $i->price,
                    'quantity' => $i->quantity,
                    'total' => $i->total,
                    'variations' => $i->variations ?? [],
                ]),
            ],
        ]);
    }

    public function track(Request $request): Response
    {
        $order = null;
        if ($request->filled('order_number')) {
            $found = Order::with(['items', 'invoice'])
                ->where('order_number', trim($request->order_number))
                ->first();

            if ($found) {
                // Public lookup by (guessable) order number — expose order contents + status only,
                // never PII like phone/email/street address.
                $order = [
                    'order_number' => $found->order_number,
                    'invoice_number' => $found->invoice?->invoice_number ?? $found->order_number,
                    'status' => $found->status,
                    'status_label' => $found->status_label,
                    'created_at' => $found->created_at->format('d M Y'),
                    'payment_method' => $found->payment_method,
                    'payment_status' => $found->payment_status,
                    'courier_name' => $found->courier_name,
                    'tracking_number' => $found->tracking_number,
                    'delivery_city' => $found->delivery_city,
                    'delivery_area' => $found->delivery_area,
                    'subtotal' => (float) $found->subtotal,
                    'delivery_charge' => (float) $found->delivery_charge,
                    'discount' => (float) $found->discount,
                    'total' => (float) $found->total,
                    'items_count' => $found->items->sum('quantity'),
                    'items' => $found->items->map(fn ($i) => [
                        'name' => $i->product_name,
                        'image' => $i->product_image,
                        'quantity' => $i->quantity,
                        'price' => (float) $i->price,
                        'total' => (float) $i->total,
                        'variations' => $i->variations ?? [],
                    ])->values(),
                ];
            }
        }

        return Inertia::render('OrderTracking', ['order' => $order]);
    }
}
