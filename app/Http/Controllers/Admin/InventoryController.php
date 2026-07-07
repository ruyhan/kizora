<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public const LOW_STOCK = 10; // fallback when the setting is absent

    /** Configurable threshold — Admin → Settings → "Low-stock alert threshold". */
    private function lowStock(): int
    {
        return max(1, (int) data_get(app(\App\Support\PageContent::class)->get('settings'), 'low_stock_threshold', self::LOW_STOCK));
    }

    private function statusOf(int $stock): string
    {
        return $stock <= 0 ? 'out' : ($stock <= $this->lowStock() ? 'low' : 'in');
    }

    public function index(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(Product::with('category:id,name'));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }
        if ($request->filled('status')) {
            match ($request->status) {
                'out' => $query->where('stock', '<=', 0),
                'low' => $query->whereBetween('stock', [1, $this->lowStock()]),
                'in'  => $query->where('stock', '>', $this->lowStock()),
                default => null,
            };
        }

        match ($request->get('sort', 'stock_asc')) {
            'stock_desc' => $query->orderByDesc('stock'),
            'name'       => $query->orderBy('name'),
            default      => $query->orderBy('stock'),
        };

        $products = $query->paginate(15)->withQueryString()->through(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'image' => $p->featured_image_url,
            'category' => $p->category?->name,
            'stock' => $p->stock,
            'status' => $this->statusOf((int) $p->stock),
            'updated_at' => $p->updated_at->format('d M Y, h:i A'),
        ]);

        return Inertia::render('Admin/Inventory/Index', [
            'products' => $products,
            'filters' => array_merge($request->only(['search', 'status', 'sort']), $dr->filters()),
            'lowThreshold' => $this->lowStock(),
            'summary' => [
                'total_products' => Product::count(),
                'total_units'    => (int) Product::sum('stock'),
                'in_stock'       => Product::where('stock', '>', $this->lowStock())->count(),
                'low_stock'      => Product::whereBetween('stock', [1, $this->lowStock()])->count(),
                'out_of_stock'   => Product::where('stock', '<=', 0)->count(),
            ],
        ]);
    }

    /** Controlled stock ADD (never overwrite). */
    public function updateStock(Request $request, Product $product)
    {
        $data = $request->validate([
            'added_quantity' => 'required|integer|min:1|max:100000',
            'note' => 'nullable|string|max:255',
        ]);

        $previous = (int) $product->stock;
        $new = $previous + $data['added_quantity'];
        $product->update(['stock' => $new]);

        InventoryLog::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'previous_stock' => $previous,
            'added_quantity' => $data['added_quantity'],
            'new_stock' => $new,
            'note' => $data['note'] ?? null,
            'created_at' => now(),
        ]);
        AuditLog::record('stock_added', 'Inventory', "+{$data['added_quantity']} to {$product->name} (now {$new})");

        return back()->with('success', "Added {$data['added_quantity']} units to {$product->name}.");
    }

    public function logs(Request $request): Response
    {
        $query = InventoryLog::with('product:id,name,sku')->latest('created_at');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('product', fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        $logs = $query->paginate(25)->withQueryString()->through(fn (InventoryLog $l) => [
            'id' => $l->id,
            'product' => $l->product?->name ?? '—',
            'sku' => $l->product?->sku,
            'previous_stock' => $l->previous_stock,
            'added_quantity' => $l->added_quantity,
            'new_stock' => $l->new_stock,
            'user' => $l->user_name ?? 'System',
            'note' => $l->note,
            'created_at' => $l->created_at?->format('d M Y, h:i A'),
        ]);

        return Inertia::render('Admin/Inventory/Logs', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
        ]);
    }

    public function analytics(): Response
    {
        $sold = fn ($since = null) => DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', 'cancelled')
            ->when($since, fn ($q) => $q->where('orders.created_at', '>=', $since))
            ->select('order_items.product_name', DB::raw('SUM(order_items.quantity) as qty'))
            ->groupBy('order_items.product_name');

        return Inertia::render('Admin/Inventory/Analytics', [
            'mostSold'    => (clone $sold())->orderByDesc('qty')->limit(8)->get(),
            'leastSold'   => (clone $sold())->orderBy('qty')->limit(8)->get(),
            'fastMoving'  => $sold(now()->subDays(30))->orderByDesc('qty')->limit(8)->get(),
            'slowMoving'  => $sold(now()->subDays(30))->orderBy('qty')->limit(8)->get(),
            'lowStock'    => Product::whereBetween('stock', [1, $this->lowStock()])->orderBy('stock')->limit(12)->get(['id', 'name', 'sku', 'stock']),
            'outOfStock'  => Product::where('stock', '<=', 0)->limit(12)->get(['id', 'name', 'sku', 'stock']),
        ]);
    }

    public function export(): StreamedResponse
    {
        AuditLog::record('export', 'Inventory', 'Exported inventory CSV');
        $products = Product::with('category:id,name')->orderBy('name')->get();

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'SKU', 'Category', 'Stock', 'Status']);
            foreach ($products as $p) {
                fputcsv($out, [$p->name, $p->sku, $p->category?->name, $p->stock, $this->statusOf((int) $p->stock)]);
            }
            fclose($out);
        }, 'inventory-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
