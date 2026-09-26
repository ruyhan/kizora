<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\DeliverySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function __construct(private readonly DeliverySettings $delivery) {}

    /** Contact name prefers the order's own snapshot (editable) over the linked account. */
    private function contactName(Order $o): string
    {
        return $o->guest_name ?: ($o->user?->name ?? 'Guest');
    }

    public function index(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');

        // Website orders only — POS & Chatbot orders live in their own sections.
        $query = $dr->applyTo(Order::web()->with('user'))->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$s}%")
                ->orWhere('guest_name', 'like', "%{$s}%")
                ->orWhere('guest_phone', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")));
        }

        $orders = $query->paginate(20)->withQueryString()->through(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'source' => $o->source,
            'customer' => $this->contactName($o),
            'status' => $o->status,
            'status_label' => $o->status_label,
            'status_color' => $o->status_color,
            'total' => $o->total,
            'payment_method' => $o->payment_method,
            'payment_status' => $o->payment_status,
            'created_at' => $o->created_at->format('d M Y'),
        ]);

        $raw = Order::web()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $raw->sum()];
        foreach (['pending', 'confirmed', 'processing', 'out_for_delivery', 'completed', 'cancelled'] as $s) {
            $counts[$s] = (int) ($raw[$s] ?? 0);
        }

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => array_merge($request->only(['status', 'search']), $dr->filters()),
            'counts' => $counts,
            // Active cities for the order-edit delivery city picker
            'cities' => \App\Models\DeliveryCity::active()->orderBy('name')->get(['id', 'name', 'delivery_charge']),
        ]);
    }

    private function payload(Order $order): array
    {
        $order->loadMissing(['items', 'user', 'invoice']);

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'invoice_number' => $order->order_number, // invoice number == order number
            'source' => $order->source,
            'status' => $order->status,
            'status_label' => $order->status_label,
            'customer' => [
                'name'  => $this->contactName($order),
                'email' => $order->guest_email ?: $order->user?->email,
                'phone' => $order->guest_phone ?: $order->user?->phone,
            ],
            'delivery_address' => $order->delivery_address,
            'delivery_city' => $order->delivery_city,
            'delivery_area' => $order->delivery_area,
            'inside_dhaka' => strcasecmp((string) $order->delivery_city, 'Dhaka') === 0,
            'subtotal' => $order->subtotal,
            'delivery_charge' => $order->delivery_charge,
            'discount' => $order->discount,
            'member_discount' => $order->member_discount,
            'privilege_card_number' => \App\Models\PrivilegeMembership::formatNumber($order->privilege_card_number),
            'coupon_code' => $order->coupon_code,
            'points_redeemed' => $order->points_redeemed,
            'points_discount' => $order->points_discount,
            'points_earned' => $order->points_earned,
            'total' => $order->total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'courier_name' => $order->courier_name,
            'tracking_number' => $order->tracking_number,
            'notes' => $order->notes,
            'created_at' => $order->created_at->format('d M Y, h:i A'),
            'items' => $order->items->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product_name' => $i->product_name,
                'product_image' => $i->product_image,
                'price' => $i->price,
                'quantity' => $i->quantity,
                'total' => $i->total,
                'variations' => $i->variations ?? [],
            ]),
        ];
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Admin/Orders/Show', ['order' => $this->payload($order)]);
    }

    public function data(Order $order): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->payload($order));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'                  => 'required|in:pending,confirmed,processing,out_for_delivery,completed,cancelled',
            'courier_name'            => 'nullable|string|max:120',
            'tracking_number'         => 'nullable|string|max:120',
            'tracking_url'            => 'nullable|url|max:500',
            'estimated_delivery_date' => 'nullable|date',
            'cancel_reason'           => 'nullable|string|max:500',
        ]);

        $from = $order->status;
        $updates = ['status' => $data['status']];

        // Persist fulfilment / cancellation details when provided (used by emails).
        foreach (['courier_name', 'tracking_number', 'tracking_url', 'estimated_delivery_date', 'cancel_reason'] as $f) {
            if ($request->filled($f)) {
                $updates[$f] = $data[$f];
            }
        }

        if ($order->payment_method === 'cod') {
            if ($data['status'] === 'completed') {
                $updates['payment_status'] = 'paid';
            } elseif ($data['status'] === 'cancelled' && $order->payment_status === 'pending') {
                $updates['payment_status'] = 'cancelled';
            }
        }
        $order->update($updates);
        AuditLog::record('status', 'Orders', "{$order->order_number}: status {$from} → {$data['status']}");

        // Notify the customer when the status changes to a stage with a template.
        if ($from !== $data['status']
            && isset(\App\Services\Email\EmailService::STATUS_TEMPLATES[$data['status']])) {
            try {
                $emails = app(\App\Services\Email\EmailService::class);
                $emails->send(
                    \App\Services\Email\EmailService::STATUS_TEMPLATES[$data['status']],
                    $order->customer_email,
                    $emails->orderVars($order),
                    $order->id,
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Status email failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Order status updated!');
    }

    /**
     * Unified order edit — works identically for website, POS and chatbot orders.
     * Handles customer/delivery/payment + items with automatic stock sync,
     * delivery recalculation and before/after audit logging.
     */
    public function update(Request $request, Order $order)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => ['nullable', new \App\Rules\BdPhone],
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'delivery_city' => 'nullable|string|max:100',
            'delivery_charge' => 'nullable|numeric|min:0|max:1000000',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|max:30',
            'payment_status' => 'nullable|in:pending,paid,failed,refunded,cancelled',
            'courier_name' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:100',
            'items' => 'nullable|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1|max:1000',
        ]);

        $before = $this->snapshot($order);

        DB::transaction(function () use ($order, $data, $user) {
            $order->load('items');

            // ── Items + stock sync (orders.edit_items) ──
            if (isset($data['items']) && $user->hasPermission('orders.edit_items')) {
                $existing = $order->items->keyBy('id');

                // Restore stock from every current line, then validate + re-deduct the new set.
                foreach ($order->items as $it) {
                    if ($it->product_id) {
                        Product::where('id', $it->product_id)->increment('stock', $it->quantity);
                    }
                }
                $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

                foreach ($data['items'] as $ni) {
                    $p = $products[$ni['product_id']];
                    if ($ni['quantity'] > $p->stock) {
                        throw ValidationException::withMessages(['items' => "Insufficient stock for {$p->name} (only {$p->stock} available)."]);
                    }
                }

                $order->items()->delete();
                foreach ($data['items'] as $ni) {
                    $p = $products[$ni['product_id']];
                    $prev = ! empty($ni['id']) ? $existing->get($ni['id']) : null;
                    $price = $prev ? (float) $prev->price : (float) $p->final_price; // preserve historical price
                    OrderItem::create([
                        'order_id' => $order->id, 'product_id' => $p->id, 'product_name' => $p->name,
                        'product_image' => $p->featured_image_url, 'price' => $price,
                        'quantity' => $ni['quantity'], 'total' => $price * $ni['quantity'],
                        'variations' => $prev?->variations,
                    ]);
                    $p->decrement('stock', $ni['quantity']);
                }
                $order->load('items');
            }

            $subtotal = (float) $order->items->sum('total');
            $update = [];

            // ── Customer / delivery (orders.edit_customer) ──
            if ($user->hasPermission('orders.edit_customer')) {
                foreach (['name' => 'guest_name', 'phone' => 'guest_phone', 'email' => 'guest_email', 'address' => 'delivery_address', 'notes' => 'notes'] as $in => $col) {
                    if (array_key_exists($in, $data)) {
                        $update[$col] = $data[$in];
                    }
                }
                if (array_key_exists('delivery_city', $data) && $data['delivery_city'] !== null && $data['delivery_city'] !== '') {
                    $update['delivery_city'] = $data['delivery_city'];
                    $update['delivery_area'] = null;
                }
            }

            // ── Payment + courier (orders.edit) ──
            if ($user->hasPermission('orders.edit')) {
                if (! empty($data['payment_method'])) {
                    $update['payment_method'] = $data['payment_method'];
                }
                if (! empty($data['payment_status'])) {
                    $update['payment_status'] = $data['payment_status'];
                }
                foreach (['courier_name', 'tracking_number'] as $col) {
                    if (array_key_exists($col, $data)) {
                        $update[$col] = $data[$col];
                    }
                }
            }

            // ── Recalculate delivery + totals (respects free-delivery rules dynamically) ──
            if ($this->delivery->locationBased()) {
                // "Delivery charge based on location": nothing was charged at checkout —
                // the admin types the exact amount here. Free delivery still wins.
                $deliveryCharge = $this->delivery->qualifiesFree($subtotal)
                    ? 0.0
                    : (isset($data['delivery_charge']) ? (float) $data['delivery_charge'] : (float) $order->delivery_charge);
            } else {
                // Resolve the charge from the order's (possibly just-changed) delivery city;
                // if that city isn't in the charges table, keep the order's existing charge.
                $cityName = $update['delivery_city'] ?? $order->delivery_city;
                $deliveryCharge = $this->delivery->chargeForCityName($cityName, $subtotal) ?? (float) $order->delivery_charge;
            }
            $discount = min((float) $order->discount, $subtotal);

            $update += [
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => $discount,
                'total' => $subtotal + $deliveryCharge - $discount,
            ];

            $order->update($update);
            Invoice::forOrder($order); // invoice number stays == order number
        });

        $order->refresh()->load('items');
        AuditLog::record('updated', 'Orders', $order->order_number . ' edited — ' . $this->diff($before, $this->snapshot($order)));

        return back()->with('success', 'Order updated.');
    }

    /**
     * Permanently delete an order (website, POS or chatbot). Restores stock for
     * orders that were not yet fulfilled, removes the invoice, and audit-logs it.
     */
    public function destroy(Order $order)
    {
        $number = $order->order_number;
        DB::transaction(fn () => $this->removeOrder($order));
        AuditLog::record('deleted', 'Orders', "{$number} deleted");

        return back()->with('success', "Order {$number} deleted.");
    }

    /** Bulk delete orders (works across website, POS and chatbot lists). */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $count = 0;
        DB::transaction(function () use ($data, &$count) {
            foreach (Order::with('items')->whereIn('id', $data['ids'])->get() as $order) {
                $this->removeOrder($order);
                $count++;
            }
        });
        AuditLog::record('deleted', 'Orders', "{$count} orders bulk-deleted");

        return back()->with('success', "{$count} order(s) deleted.");
    }

    /** Delete one order, restoring stock for orders that weren't yet fulfilled. */
    private function removeOrder(Order $order): void
    {
        $order->loadMissing('items');
        if ($order->status !== 'completed') { // completed = goods already sold
            foreach ($order->items as $it) {
                if ($it->product_id) {
                    Product::where('id', $it->product_id)->increment('stock', $it->quantity);
                }
            }
        }
        $order->invoice?->delete();
        $order->items()->delete();
        $order->delete();
    }

    public function productsSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        $products = Product::where('is_active', true)
            ->when($q !== '', fn ($b) => $b->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%")))
            ->orderBy('name')->limit(15)->get()
            ->map(fn (Product $p) => [
                'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
                'price' => (float) $p->final_price, 'stock' => (int) $p->stock, 'image' => $p->featured_image_url,
            ]);

        return response()->json($products);
    }

    private function snapshot(Order $order): array
    {
        return [
            'name' => $this->contactName($order),
            'phone' => $order->guest_phone ?: $order->user?->phone,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'items' => $order->items()->count(),
            'total' => (float) $order->total,
        ];
    }

    /** Concise before→after summary for the audit log. */
    private function diff(array $before, array $after): string
    {
        $parts = [];
        foreach ($after as $k => $v) {
            if (($before[$k] ?? null) != $v) {
                $parts[] = "{$k}: " . ($before[$k] ?? '—') . " → {$v}";
            }
        }
        return $parts ? implode(', ', $parts) : 'no field changes';
    }
}
