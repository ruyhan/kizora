<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChatbotConversation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ChatbotController extends Controller
{
    public function orders(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(Order::chatbot()->with(['user:id,name', 'invoice']))->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('order_number', 'like', "%{$s}%")
                ->orWhere('guest_name', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15)->withQueryString()->through(fn (Order $o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'invoice_number' => $o->invoice?->invoice_number,
            'customer' => $o->user?->name ?? $o->guest_name ?? 'Guest',
            'status' => $o->status,
            'status_label' => $o->status_label,
            'payment_method' => $o->payment_method,
            'total' => $o->total,
            'created_at' => $o->created_at->format('d M Y, h:i A'),
        ]);

        return Inertia::render('Admin/Chatbot/Orders', [
            'orders' => $orders,
            'filters' => array_merge($request->only(['search', 'status']), $dr->filters()),
        ]);
    }

    public function analytics(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, '30');
        $ev = fn ($q) => $q->when($dr->start, fn ($x) => $x->where('chatbot_events.created_at', '>=', $dr->start))
            ->when($dr->end, fn ($x) => $x->where('chatbot_events.created_at', '<=', $dr->end));
        $ord = fn ($q) => $q->when($dr->start, fn ($x) => $x->where('orders.created_at', '>=', $dr->start))
            ->when($dr->end, fn ($x) => $x->where('orders.created_at', '<=', $dr->end));

        $totalConversations = $dr->applyTo(ChatbotConversation::query())->count();
        $converted = $dr->applyTo(ChatbotConversation::whereNotNull('order_id'))->count();
        $ordersCount = $dr->applyTo(Order::chatbot())->count();
        $revenue = (float) $dr->applyTo(Order::chatbot()->where('status', '!=', 'cancelled'))->sum('total');

        $eventAgg = fn (string $type) => $ev(DB::table('chatbot_events')
            ->join('products', 'products.id', '=', 'chatbot_events.product_id')
            ->where('chatbot_events.type', $type))
            ->select('products.name', DB::raw('COUNT(*) as count'))
            ->groupBy('products.name')->orderByDesc('count')->limit(8)->get();

        return Inertia::render('Admin/Chatbot/Analytics', [
            'filters' => $dr->filters(),
            'kpis' => [
                'conversations' => $totalConversations,
                'orders' => $ordersCount,
                'revenue' => round($revenue, 2),
                'conversion_rate' => $totalConversations ? round($converted / $totalConversations * 100, 1) : 0,
            ],
            'mostAsked' => $eventAgg('product_shown'),
            'mostClicked' => $eventAgg('product_click'),
            'leadingToOrders' => $ord(DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.source', 'chatbot')->where('orders.status', '!=', 'cancelled'))
                ->select('order_items.product_name as name', DB::raw('SUM(order_items.quantity) as count'))
                ->groupBy('order_items.product_name')->orderByDesc('count')->limit(8)->get(),
            'topQuestions' => $ev(DB::table('chatbot_events')->where('type', 'query')->whereNotNull('query'))
                ->select('query', DB::raw('COUNT(*) as count'))
                ->groupBy('query')->orderByDesc('count')->limit(10)->get(),
            'topCategories' => $ev(DB::table('chatbot_events')
                ->join('products', 'products.id', '=', 'chatbot_events.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->where('chatbot_events.type', 'product_shown'))
                ->select('categories.name', DB::raw('COUNT(*) as count'))
                ->groupBy('categories.name')->orderByDesc('count')->limit(8)->get(),
        ]);
    }

    public function conversations(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(ChatbotConversation::withCount('messages')->with('user:id,name', 'order:id,order_number'));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', "%{$s}%"))
                ->orWhereHas('messages', fn ($m) => $m->where('content', 'like', "%{$s}%")));
        }

        $conversations = $query->latest()->paginate(20)->withQueryString()->through(fn (ChatbotConversation $c) => [
                'id' => $c->id,
                'user' => $c->user?->name ?? 'Guest',
                'messages_count' => $c->messages_count,
                'order' => $c->order?->order_number,
                'created_at' => $c->created_at->format('d M Y, h:i A'),
                'messages' => $c->messages()->orderBy('id')->get(['role', 'content', 'created_at'])
                    ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content]),
            ]);

        return Inertia::render('Admin/Chatbot/Conversations', [
            'conversations' => $conversations,
            'filters' => array_merge($request->only(['search']), $dr->filters()),
        ]);
    }
}
