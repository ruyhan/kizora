<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AbandonedCartController extends Controller
{
    /** Carts (grouped per customer/guest session) that never became orders. */
    public function index(Request $request): Response
    {
        $query = Cart::with(['product:id,name,price,discount_price,sale_starts_at,sale_ends_at,featured_image,stock', 'user:id,name,email,phone'])
            ->latest('updated_at');

        // Only show carts idle for at least an hour — active shoppers aren't "abandoned".
        $query->where('updated_at', '<=', now()->subHour());

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }

        $items = $query->get();

        // Group lines into one "cart" per user / guest session
        $carts = $items->groupBy(fn ($i) => $i->user_id ? "u{$i->user_id}" : "s{$i->session_id}")
            ->map(function ($lines) {
                $first = $lines->first();
                return [
                    'key'        => $first->user_id ? "u{$first->user_id}" : "s{$first->session_id}",
                    'customer'   => $first->user
                        ? ['name' => $first->user->name, 'email' => $first->user->email, 'phone' => $first->user->phone]
                        : null,
                    'items'      => $lines->map(fn ($l) => [
                        'id'         => $l->id,
                        'name'       => $l->product?->name ?? 'Deleted product',
                        'image'      => $l->product?->featured_image_url,
                        'quantity'   => $l->quantity,
                        'price'      => $l->product ? $l->product->priceForVariations($l->variations) : 0,
                        'variations' => $l->variations ?? [],
                    ])->values(),
                    'total'      => $lines->sum(fn ($l) => $l->product ? $l->product->priceForVariations($l->variations) * $l->quantity : 0),
                    'item_count' => $lines->sum('quantity'),
                    'updated_at' => $lines->max('updated_at')->diffForHumans(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return Inertia::render('Admin/Carts/Index', [
            'carts'   => $carts,
            'stats'   => [
                'carts' => $carts->count(),
                'items' => $carts->sum('item_count'),
                'value' => $carts->sum('total'),
            ],
            'filters' => $request->only(['search']),
        ]);
    }
}
