<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\ContactMessage;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $dr = DateRange::fromRequest($request, '30');
        $hasPrev = (bool) $dr->prevStart;

        // % change; null when there's no prior baseline (avoids misleading +100%)
        $pct = function (float $current, float $previous): ?int {
            if ($previous <= 0) return null;
            return (int) round((($current - $previous) / $previous) * 100);
        };
        $prevCount = fn ($q) => $hasPrev ? $q->whereBetween('created_at', [$dr->prevStart, $dr->prevEnd])->count() : 0;
        $prevSum = fn ($q) => $hasPrev ? (float) $q->whereBetween('created_at', [$dr->prevStart, $dr->prevEnd])->sum('total') : 0;

        $consumers = fn () => User::whereHas('role', fn ($q) => $q->where('slug', 'consumer'));

        $ordersCur = $dr->applyTo(Order::query())->count();
        $revCur    = (float) $dr->applyTo(Order::where('status', '!=', 'cancelled'))->sum('total');
        $pendCur   = $dr->applyTo(Order::where('status', 'pending'))->count();
        $custCur   = $dr->applyTo($consumers())->count();

        $stats = [
            'total_orders'     => $ordersCur,
            'pending_orders'   => $pendCur,
            'completed_orders' => $dr->applyTo(Order::where('status', 'completed'))->count(),
            'total_revenue'    => round($revCur, 2),
            'total_products'   => Product::count(),                 // catalog size (all-time)
            'total_customers'  => $custCur,
            'unread_messages'  => ContactMessage::where('is_read', false)->count(),
            'orders_change'    => $hasPrev ? $pct($ordersCur, $prevCount(Order::query())) : null,
            'revenue_change'   => $hasPrev ? $pct($revCur, $prevSum(Order::where('status', '!=', 'cancelled'))) : null,
            'pending_change'   => $hasPrev ? $pct($pendCur, $prevCount(Order::where('status', 'pending'))) : null,
            'customers_change' => $hasPrev ? $pct($custCur, $prevCount($consumers())) : null,
        ];

        $recentOrders = $dr->applyTo(Order::with('user'))
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

        $salesData = $dr->applyTo(Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders')
            ))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($dr->start, fn ($q) => $q->where('orders.created_at', '>=', $dr->start))
            ->when($dr->end, fn ($q) => $q->where('orders.created_at', '<=', $dr->end))
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as sold'), DB::raw('SUM(order_items.total) as revenue'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('sold')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'filters' => $dr->filters(),
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'salesData' => $salesData,
            'topProducts' => $topProducts,
        ]);
    }
}
