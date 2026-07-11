<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /** Consumers = non-staff accounts. */
    private function consumerQuery()
    {
        return User::query()->where(function ($q) {
            $q->whereHas('role', fn ($r) => $r->where('is_staff', false))->orWhereNull('role_id');
        });
    }

    public function index(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo($this->consumerQuery())   // join-date range
            ->withCount('orders')
            ->withSum('orders as lifetime_spend', 'total')
            ->withMax('orders as last_order_at', 'created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'spend'  => $query->orderByDesc('lifetime_spend'),
            'orders' => $query->orderByDesc('orders_count'),
            default  => $query->latest(),
        };

        $customers = $query->paginate(15)->withQueryString()->through(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'avatar' => $u->avatar,
            'status' => $u->status,
            'orders_count' => $u->orders_count,
            'lifetime_spend' => round((float) $u->lifetime_spend, 2),
            'last_order_at' => $u->last_order_at ? \Illuminate\Support\Carbon::parse($u->last_order_at)->format('d M Y') : null,
            'created_at' => $u->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => array_merge($request->only(['search', 'status', 'sort']), $dr->filters()),
            'totals' => [
                'count' => $this->consumerQuery()->count(),
                'active' => $this->consumerQuery()->where('status', 'active')->count(),
            ],
        ]);
    }

    public function orders(Request $request, User $customer): Response
    {
        abort_unless($customer->isConsumer(), 404);

        $query = $customer->orders()->withCount('items')->with('items:id,order_id,product_name');

        if ($request->filled('search')) {
            $query->where('order_number', 'like', "%{$request->search}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest()->paginate(12)->withQueryString()->through(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'payment_status' => $o->payment_status,
            'total' => $o->total,
            'items_count' => $o->items_count,
            'products' => $o->items->pluck('product_name')->take(3)->all(),
            'created_at' => $o->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Customers/Orders', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'avatar' => $customer->avatar,
                'status' => $customer->status,
                'created_at' => $customer->created_at->format('d M Y'),
                'orders_count' => $customer->orders()->count(),
                'lifetime_spend' => round((float) $customer->orders()->sum('total'), 2),
                'last_order_at' => optional($customer->orders()->max('created_at'))
                    ? \Illuminate\Support\Carbon::parse($customer->orders()->max('created_at'))->format('d M Y') : null,
            ],
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status']),
        ]);
    }

    /** JSON: a customer's orders, for the in-place "View orders" popup. */
    public function ordersData(User $customer): \Illuminate\Http\JsonResponse
    {
        abort_unless($customer->isConsumer(), 404);

        $orders = $customer->orders()->withCount('items')->latest()->get()->map(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'source' => $o->source,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'payment_status' => $o->payment_status,
            'total' => $o->total,
            'items_count' => $o->items_count,
            'created_at' => $o->created_at->format('d M Y'),
        ]);

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'status' => $customer->status,
                'orders_count' => $orders->count(),
                'lifetime_spend' => round((float) $customer->orders()->sum('total'), 2),
            ],
            'orders' => $orders,
        ]);
    }

    /** Edit a consumer's profile + account status (active / inactive / blocked). */
    public function update(Request $request, User $customer)
    {
        abort_unless($customer->isConsumer(), 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $from = $customer->status;
        $customer->update($data);
        AuditLog::record('updated', 'Customers', "Updated {$customer->name}" . ($from !== $data['status'] ? " (status {$from} → {$data['status']})" : ''));

        return back()->with('success', 'Customer updated.');
    }

    /** Delete a single consumer account (staff/admin accounts are never deletable here). */
    public function destroy(User $customer)
    {
        abort_unless($customer->isConsumer(), 404);

        $name = $customer->name;
        // Orders keep their history (user_id is set to null); carts, wishlists,
        // reviews and addresses are removed by their cascade constraints.
        $customer->delete();
        AuditLog::record('deleted', 'Customers', "Deleted customer {$name}");

        return back()->with('success', 'Customer deleted.');
    }

    /** Delete several consumer accounts at once. */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        // Only ever delete consumer accounts — silently skip any staff/admin ids.
        $customers = $this->consumerQuery()->whereIn('id', $data['ids'])->get();
        $count = 0;
        foreach ($customers as $customer) {
            $customer->delete();
            $count++;
        }

        AuditLog::record('deleted', 'Customers', "Bulk-deleted {$count} customer(s)");

        return back()->with('success', "{$count} customer(s) deleted.");
    }

    /** Set a new password for a consumer account. */
    public function resetPassword(Request $request, User $customer)
    {
        abort_unless($customer->isConsumer(), 404);

        $request->validate(['password' => 'required|string|min:8']);
        $customer->update(['password' => Hash::make($request->password)]);
        AuditLog::record('reset_password', 'Customers', "Reset password for {$customer->name}");

        return back()->with('success', 'Password reset.');
    }
}
