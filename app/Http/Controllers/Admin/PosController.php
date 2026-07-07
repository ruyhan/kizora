<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\DeliverySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public const PAYMENT_METHODS = ['cash', 'card', 'mobile_banking', 'bank_transfer', 'other'];

    public function __construct(private readonly DeliverySettings $delivery) {}

    public function create(): Response
    {
        return Inertia::render('Admin/Pos/Create', [
            'products' => Product::where('is_active', true)
                ->select('id', 'name', 'sku', 'price', 'discount_price', 'sale_starts_at', 'sale_ends_at', 'featured_image', 'stock', 'category_id')
                ->with('category:id,name')
                ->orderBy('name')->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id, 'name' => $p->name, 'sku' => $p->sku,
                    'price' => (float) $p->final_price, 'stock' => (int) $p->stock,
                    'image' => $p->featured_image_url, 'category' => $p->category?->name,
                ]),
            'customers' => User::whereHas('role', fn ($q) => $q->where('is_staff', false))
                ->orWhereNull('role_id')
                ->select('id', 'name', 'phone', 'email', 'delivery_address')->orderBy('name')->get(),
            'deliveryCities' => DeliveryCity::active()->orderBy('name')->get(['id', 'name', 'delivery_charge']),
            'deliveryAreas'  => DeliveryArea::whereHas('city', fn ($q) => $q->where('is_active', true))
                ->orderBy('name')->get(['id', 'delivery_city_id', 'name', 'postal_code']),
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'customer_type' => 'required|in:existing,walkin',
            'customer_id' => 'required_if:customer_type,existing|nullable|exists:users,id',
            'name' => 'required_if:customer_type,walkin|nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'delivery_area_id' => 'required|exists:delivery_areas,id',
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHODS)],
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

        // Stock guard — prevent overselling
        foreach ($data['items'] as $line) {
            $p = $products[$line['product_id']];
            if ($line['quantity'] > $p->stock) {
                return back()->withErrors(['items' => "Insufficient stock for {$p->name} (only {$p->stock} left)."]);
            }
        }

        $subtotal = collect($data['items'])->sum(fn ($l) => (float) $products[$l['product_id']]->final_price * $l['quantity']);
        $area = DeliveryArea::with('city')->find($data['delivery_area_id']);
        // POS is staff-driven: an unset city charge falls back to 0 (admin can adjust) rather than blocking.
        $deliveryCharge = $this->delivery->chargeFor($area, $subtotal) ?? 0.0;
        $discount = min((float) ($data['discount'] ?? 0), $subtotal);
        $total = $subtotal + $deliveryCharge - $discount;

        $customer = $data['customer_type'] === 'existing' ? User::find($data['customer_id']) : null;

        $order = DB::transaction(function () use ($data, $products, $subtotal, $deliveryCharge, $discount, $total, $area, $customer) {
            $order = Order::create([
                'source' => 'pos',
                'user_id' => $customer?->id,
                'guest_name' => $customer ? null : ($data['name'] ?? 'Walk-in Customer'),
                'guest_phone' => $customer ? null : ($data['phone'] ?? null),
                'guest_email' => $customer ? null : ($data['email'] ?? null),
                'delivery_address' => $data['address'] ?? null,
                'delivery_city' => $area?->city?->name,
                'delivery_area' => $area?->name,
                'delivery_postal_code' => $area?->postal_code,
                'status' => 'completed',
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'paid',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $line) {
                $p = $products[$line['product_id']];
                $price = (float) $p->final_price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'product_image' => $p->featured_image_url,
                    'price' => $price,
                    'quantity' => $line['quantity'],
                    'total' => $price * $line['quantity'],
                ]);
                $p->decrement('stock', $line['quantity']); // sync inventory
            }

            Invoice::forOrder($order); // generate invoice number
            return $order;
        });

        AuditLog::record('created', 'POS', "POS order {$order->order_number} (৳" . number_format($total, 2) . ')');

        return redirect()->route('admin.pos.orders')
            ->with('success', "POS order {$order->order_number} completed.")
            ->with('pos_order_id', $order->id);
    }

    public function orders(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(Order::pos()->with(['user:id,name', 'invoice']))->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
                ->orWhere('guest_name', 'like', "%{$s}%"));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->paginate(15)->withQueryString()->through(fn (Order $o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'invoice_number' => $o->invoice?->invoice_number,
            'customer' => $o->user?->name ?? $o->guest_name ?? 'Walk-in',
            'payment_method' => $o->payment_method,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'total' => $o->total,
            'created_at' => $o->created_at->format('d M Y, h:i A'),
        ]);

        return Inertia::render('Admin/Pos/Orders', [
            'orders' => $orders,
            'filters' => array_merge($request->only(['search', 'payment_method']), $dr->filters()),
            'paymentMethods' => self::PAYMENT_METHODS,
        ]);
    }
}
