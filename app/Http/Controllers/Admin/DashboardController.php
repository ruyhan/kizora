<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        // ── Period-over-period windows (last 30 days vs the prior 30) ──
        $now       = now();
        $curStart  = $now->copy()->subDays(30);
        $prevStart = $now->copy()->subDays(60);

        // % change; null when there's no prior baseline (avoids misleading +100%)
        $pct = function (float $current, float $previous): ?int {
            if ($previous <= 0) return null;
            return (int) round((($current - $previous) / $previous) * 100);
        };

        $ordersCur  = Order::whereBetween('created_at', [$curStart, $now])->count();
        $ordersPrev = Order::whereBetween('created_at', [$prevStart, $curStart])->count();

        // Revenue = all non-cancelled orders (consistent with Reports & Sales Analysis).
        $revCur  = (float) Order::where('status', '!=', 'cancelled')->whereBetween('created_at', [$curStart, $now])->sum('total');
        $revPrev = (float) Order::where('status', '!=', 'cancelled')->whereBetween('created_at', [$prevStart, $curStart])->sum('total');

        $pendCur  = Order::where('status', 'pending')->whereBetween('created_at', [$curStart, $now])->count();
        $pendPrev = Order::where('status', 'pending')->whereBetween('created_at', [$prevStart, $curStart])->count();

        $consumers = fn () => User::whereHas('role', fn ($q) => $q->where('slug', 'consumer'));
        $custCur  = $consumers()->whereBetween('created_at', [$curStart, $now])->count();
        $custPrev = $consumers()->whereBetween('created_at', [$prevStart, $curStart])->count();

        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'completed')->count(),
            'total_revenue' => Order::where('status', '!=', 'cancelled')->sum('total'),
            'total_products' => Product::count(),
            'total_customers' => User::whereHas('role', fn ($q) => $q->where('slug', 'consumer'))->count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),
            // real deltas
            'orders_change'    => $pct($ordersCur, $ordersPrev),
            'revenue_change'   => $pct($revCur, $revPrev),
            'pending_change'   => $pct($pendCur, $pendPrev),
            'customers_change' => $pct($custCur, $custPrev),
        ];

        $recentOrders = Order::with('user')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer' => $o->user?->name ?? $o->guest_name ?? 'Guest',
                'status' => $o->status,
                'status_label' => $o->status_label,
                'status_color' => $o->status_color,
                'total' => $o->total,
                'payment_method' => $o->payment_method,
                'created_at' => $o->created_at->format('d M Y'),
            ]);

        $salesData = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = DB::table('order_items')
            ->select('product_name', DB::raw('SUM(quantity) as sold'), DB::raw('SUM(total) as revenue'))
            ->groupBy('product_name')
            ->orderByDesc('sold')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'salesData' => $salesData,
            'topProducts' => $topProducts,
        ]);
    }
}
