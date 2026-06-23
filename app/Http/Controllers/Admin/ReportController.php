<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const RANGES = ['7', '30', '90', '365'];

    /** Resolve the range key into [start, prevStart, days|null]. */
    private function window(string $range): array
    {
        $days = in_array($range, self::RANGES, true) ? (int) $range : null;
        $now = now();
        return [
            $days ? $now->copy()->subDays($days) : null,
            $days ? $now->copy()->subDays($days * 2) : null,
            $days,
        ];
    }

    public function index(Request $request): Response
    {
        $range = (string) $request->get('range', '30');
        [$start, $prevStart] = $this->window($range);

        $pct = function (float $cur, float $prev): ?int {
            if ($prev <= 0) return null;
            return (int) round((($cur - $prev) / $prev) * 100);
        };

        // Revenue counts every non-cancelled order.
        $revenueQ = fn ($from, $to) => (float) Order::where('status', '!=', 'cancelled')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))
            ->sum('total');
        $ordersQ = fn ($from, $to) => Order::when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))->count();
        $custQ = fn ($from, $to) => User::whereHas('role', fn ($q) => $q->where('slug', 'consumer'))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<', $to))->count();

        $revenue = $revenueQ($start, null);
        $orders = $ordersQ($start, null);
        $newCustomers = $custQ($start, null);
        $aov = $orders > 0 ? $revenue / $orders : 0;

        // Previous comparable period (only when a finite range is selected)
        $revPrev = $start ? $revenueQ($prevStart, $start) : 0;
        $ordPrev = $start ? $ordersQ($prevStart, $start) : 0;
        $custPrev = $start ? $custQ($prevStart, $start) : 0;
        $aovPrev = $ordPrev > 0 ? $revPrev / $ordPrev : 0;

        $kpis = [
            'revenue'       => round($revenue, 2),
            'orders'        => $orders,
            'aov'           => round($aov, 2),
            'new_customers' => $newCustomers,
            'revenue_change'  => $start ? $pct($revenue, $revPrev) : null,
            'orders_change'   => $start ? $pct($orders, $ordPrev) : null,
            'aov_change'      => $start ? $pct($aov, $aovPrev) : null,
            'customers_change' => $start ? $pct($newCustomers, $custPrev) : null,
        ];

        return Inertia::render('Admin/Reports/Index', [
            'range' => $range,
            'kpis' => $kpis,
            'daily' => $this->dailySales($start),
            'byStatus' => Order::when($start, fn ($q) => $q->where('created_at', '>=', $start))
                ->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'byPayment' => Order::when($start, fn ($q) => $q->where('created_at', '>=', $start))
                ->selectRaw('payment_status, count(*) as c')->groupBy('payment_status')->pluck('c', 'payment_status'),
            'topProducts' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->when($start, fn ($q) => $q->where('orders.created_at', '>=', $start))
                ->where('orders.status', '!=', 'cancelled')
                ->select('product_name', DB::raw('SUM(quantity) as sold'), DB::raw('SUM(order_items.total) as revenue'))
                ->groupBy('product_name')->orderByDesc('sold')->take(8)->get(),
            'topCustomers' => $this->topCustomers($start),
            'canExport' => $request->user()->hasPermission('reports.export'),
        ]);
    }

    /** Total Sales Report — combines Website, POS and Chatbot channels. */
    public function salesAnalysis(): Response
    {
        $rev = fn ($q) => (float) $q->where('status', '!=', 'cancelled')->sum('total');

        $byChannel = [
            ['channel' => 'Website', 'orders' => Order::web()->count(),     'revenue' => $rev(Order::web())],
            ['channel' => 'POS',     'orders' => Order::pos()->count(),     'revenue' => $rev(Order::pos())],
            ['channel' => 'Chatbot', 'orders' => Order::chatbot()->count(), 'revenue' => $rev(Order::chatbot())],
        ];
        $totalRevenue = array_sum(array_column($byChannel, 'revenue'));
        $paidOrders = Order::where('status', '!=', 'cancelled')->count();

        return Inertia::render('Admin/Reports/SalesAnalysis', [
            'kpis' => [
                'revenue' => round($totalRevenue, 2),
                'orders' => Order::count(),
                'aov' => $paidOrders ? round($totalRevenue / $paidOrders, 2) : 0,
                'units' => (int) \Illuminate\Support\Facades\DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('orders.status', '!=', 'cancelled')->sum('order_items.quantity'),
            ],
            'byChannel' => $byChannel,
            'byStatus' => Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'daily' => $this->dailySales(now()->subDays(30)),
            'topProducts' => \Illuminate\Support\Facades\DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->select('order_items.product_name', \Illuminate\Support\Facades\DB::raw('SUM(order_items.quantity) as sold'), \Illuminate\Support\Facades\DB::raw('SUM(order_items.total) as revenue'))
                ->groupBy('order_items.product_name')->orderByDesc('revenue')->limit(8)->get(),
            'canExport' => request()->user()->hasPermission('reports.export'),
        ]);
    }

    public function salesExport(): StreamedResponse
    {
        AuditLog::record('export', 'Reports', 'Exported total sales report');
        $rev = fn ($q) => (float) $q->where('status', '!=', 'cancelled')->sum('total');
        $rows = [
            ['Website', Order::web()->count(), $rev(Order::web())],
            ['POS', Order::pos()->count(), $rev(Order::pos())],
            ['Chatbot', Order::chatbot()->count(), $rev(Order::chatbot())],
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

    private function dailySales($start)
    {
        return Order::where('status', '!=', 'cancelled')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('date')->orderBy('date')->get();
    }

    private function topCustomers($start)
    {
        $rows = Order::whereNotNull('user_id')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->where('status', '!=', 'cancelled')
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
        $range = (string) $request->get('range', '30');
        [$start] = $this->window($range);
        AuditLog::record('export', 'Reports', "Exported sales report ({$range})");

        $rows = $this->dailySales($start);

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
