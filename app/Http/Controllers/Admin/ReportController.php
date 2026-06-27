<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $dr = DateRange::fromRequest($request, '30');

        $pct = function (float $cur, float $prev): ?int {
            if ($prev <= 0) return null;
            return (int) round((($cur - $prev) / $prev) * 100);
        };

        $revenueQ = fn ($from, $to) => (float) Order::where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->sum('total');
        $ordersQ = fn ($from, $to) => Order::when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))->count();
        $custQ = fn ($from, $to) => User::whereHas('role', fn ($q) => $q->where('slug', 'consumer'))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))->count();

        $revenue = $revenueQ($dr->start, $dr->end);
        $orders = $ordersQ($dr->start, $dr->end);
        $newCustomers = $custQ($dr->start, $dr->end);
        $aov = $orders > 0 ? $revenue / $orders : 0;

        $hasPrev = (bool) $dr->prevStart;
        $revPrev = $hasPrev ? $revenueQ($dr->prevStart, $dr->prevEnd) : 0;
        $ordPrev = $hasPrev ? $ordersQ($dr->prevStart, $dr->prevEnd) : 0;
        $custPrev = $hasPrev ? $custQ($dr->prevStart, $dr->prevEnd) : 0;
        $aovPrev = $ordPrev > 0 ? $revPrev / $ordPrev : 0;

        $kpis = [
            'revenue'       => round($revenue, 2),
            'orders'        => $orders,
            'aov'           => round($aov, 2),
            'new_customers' => $newCustomers,
            'revenue_change'   => $hasPrev ? $pct($revenue, $revPrev) : null,
            'orders_change'    => $hasPrev ? $pct($orders, $ordPrev) : null,
            'aov_change'       => $hasPrev ? $pct($aov, $aovPrev) : null,
            'customers_change' => $hasPrev ? $pct($newCustomers, $custPrev) : null,
        ];

        return Inertia::render('Admin/Reports/Index', [
            'filters' => $dr->filters(),
            'kpis' => $kpis,
            'daily' => $this->dailySales($dr),
            'byStatus' => $dr->applyTo(Order::query())
                ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'byPayment' => $dr->applyTo(Order::query())
                ->selectRaw('payment_status, count(*) as c')->groupBy('payment_status')->pluck('c', 'payment_status'),
            'topProducts' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->when($dr->start, fn ($q) => $q->where('orders.created_at', '>=', $dr->start))
                ->when($dr->end, fn ($q) => $q->where('orders.created_at', '<=', $dr->end))
                ->select('product_name', DB::raw('SUM(quantity) as sold'), DB::raw('SUM(order_items.total) as revenue'))
                ->groupBy('product_name')->orderByDesc('sold')->take(8)->get(),
            'topCustomers' => $this->topCustomers($dr),
            'canExport' => $request->user()->hasPermission('reports.export'),
        ]);
    }

    /** Total Sales Report — combines Website, POS and Chatbot channels. */
    public function salesAnalysis(Request $request): Response
    {
        $dr = DateRange::fromRequest($request, '30');

        $rev = fn ($q) => (float) $dr->applyTo($q->where('status', '!=', 'cancelled'))->sum('total');
        $cnt = fn ($q) => $dr->applyTo($q)->count();

        $byChannel = [
            ['channel' => 'Website', 'orders' => $cnt(Order::web()),     'revenue' => $rev(Order::web())],
            ['channel' => 'POS',     'orders' => $cnt(Order::pos()),     'revenue' => $rev(Order::pos())],
            ['channel' => 'Chatbot', 'orders' => $cnt(Order::chatbot()), 'revenue' => $rev(Order::chatbot())],
        ];
        $totalRevenue = array_sum(array_column($byChannel, 'revenue'));
        $paidOrders = $dr->applyTo(Order::where('status', '!=', 'cancelled'))->count();

        return Inertia::render('Admin/Reports/SalesAnalysis', [
            'filters' => $dr->filters(),
            'kpis' => [
                'revenue' => round($totalRevenue, 2),
                'orders' => $dr->applyTo(Order::query())->count(),
                'aov' => $paidOrders ? round($totalRevenue / $paidOrders, 2) : 0,
                'units' => (int) DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.status', '!=', 'cancelled')
                    ->when($dr->start, fn ($q) => $q->where('orders.created_at', '>=', $dr->start))
                    ->when($dr->end, fn ($q) => $q->where('orders.created_at', '<=', $dr->end))
                    ->sum('order_items.quantity'),
            ],
            'byChannel' => $byChannel,
            'byStatus' => $dr->applyTo(Order::query())->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'daily' => $this->dailySales($dr),
            'topProducts' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->when($dr->start, fn ($q) => $q->where('orders.created_at', '>=', $dr->start))
                ->when($dr->end, fn ($q) => $q->where('orders.created_at', '<=', $dr->end))
                ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as sold'), DB::raw('SUM(order_items.total) as revenue'))
                ->groupBy('order_items.product_name')->orderByDesc('revenue')->limit(8)->get(),
            'canExport' => $request->user()->hasPermission('reports.export'),
        ]);
    }

    public function salesExport(Request $request): StreamedResponse
    {
        $dr = DateRange::fromRequest($request, '30');
        AuditLog::record('export', 'Reports', 'Exported total sales report (' . $dr->label . ')');

        $rev = fn ($q) => (float) $dr->applyTo($q->where('status', '!=', 'cancelled'))->sum('total');
        $cnt = fn ($q) => $dr->applyTo($q)->count();
        $rows = [
            ['Website', $cnt(Order::web()),     $rev(Order::web())],
            ['POS',     $cnt(Order::pos()),     $rev(Order::pos())],
            ['Chatbot', $cnt(Order::chatbot()), $rev(Order::chatbot())],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Channel', 'Orders', 'Revenue']);
            foreach ($rows as $r) {
                fputcsv($out, [$r[0], $r[1], number_format($r[2], 2, '.', '')]);
            }
            fclose($out);
        }, 'total-sales-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function dailySales(DateRange $dr)
    {
        return $dr->applyTo(Order::where('status', '!=', 'cancelled'))
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('date')->orderBy('date')->get();
    }

    private function topCustomers(DateRange $dr)
    {
        $rows = $dr->applyTo(Order::whereNotNull('user_id')->where('status', '!=', 'cancelled'))
            ->selectRaw('user_id, COUNT(*) as orders, SUM(total) as spend')
            ->groupBy('user_id')->orderByDesc('spend')->take(8)->get();
        $names = User::whereIn('id', $rows->pluck('user_id'))->pluck('name', 'id');

        return $rows->map(fn ($r) => [
            'name' => $names[$r->user_id] ?? '—',
            'orders' => $r->orders,
            'spend' => round((float) $r->spend, 2),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $dr = DateRange::fromRequest($request, '30');
        AuditLog::record('export', 'Reports', "Exported sales report ({$dr->label})");

        $rows = $this->dailySales($dr);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Orders', 'Revenue']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->date, $r->orders, number_format((float) $r->revenue, 2, '.', '')]);
            }
            fclose($out);
        }, 'sales-report-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
