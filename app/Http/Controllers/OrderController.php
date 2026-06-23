<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(): Response
    {
        $orders = Order::with('items')
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
            ]);

        return Inertia::render('Account/Orders', ['orders' => $orders]);
    }

    public function show(Order $order): Response
    {
        if ($order->user_id !== Auth::id()) abort(403);

        $order->load('items');

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
                'delivery_address' => $order->delivery_address,
                'delivery_city' => $order->delivery_city,
                'notes' => $order->notes,
                'created_at' => $order->created_at->format('d M Y, h:i A'),
                'items' => $order->items->map(fn($i) => [
                    'id' => $i->id,
                    'product_name' => $i->product_name,
                    'product_image' => $i->product_image,
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
            $order = Order::with('items')
                ->where('order_number', $request->order_number)
                ->first();

            if ($order) {
                $order = [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_label' => $order->status_label,
                    'status_color' => $order->status_color,
                    'created_at' => $order->created_at->format('d M Y'),
                    'items_count' => $order->items->count(),
                    'total' => $order->total,
                ];
            }
        }

        return Inertia::render('OrderTracking', ['order' => $order]);
    }
}
