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
        $query = Order::chatbot()->with(['user:id,name', 'invoice'])->latest();

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
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function analytics(): Response
    {
        $totalConversations = ChatbotConversation::count();
        $converted = ChatbotConversation::whereNotNull('order_id')->count();
        $ordersCount = Order::chatbot()->count();
        $revenue = (float) Order::chatbot()->where('status', '!=', 'cancelled')->sum('total');

        $eventAgg = fn (string $type) => DB::table('chatbot_events')
            ->join('products', 'products.id', '=', 'chatbot_events.product_id')
            ->where('chatbot_events.type', $type)
            ->select('products.name', DB::raw('COUNT(*) as count'))
            ->groupBy('products.name')->orderByDesc('count')->limit(8)->get();

        return Inertia::render('Admin/Chatbot/Analytics', [
            'kpis' => [
                'conversations' => $totalConversations,
                'orders' => $ordersCount,
                'revenue' => round($revenue, 2),
                'conversion_rate' => $totalConversations ? round($converted / $totalConversations * 100, 1) : 0,
            ],
            'mostAsked' => $eventAgg('product_shown'),
            'mostClicked' => $eventAgg('product_click'),
            'leadingToOrders' => DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.source', 'chatbot')->where('orders.status', '!=', 'cancelled')
                ->select('order_items.product_name as name', DB::raw('SUM(order_items.quantity) as count'))
                ->groupBy('order_items.product_name')->orderByDesc('count')->limit(8)->get(),
            'topQuestions' => DB::table('chatbot_events')->where('type', 'query')->whereNotNull('query')
                ->select('query', DB::raw('COUNT(*) as count'))
                ->groupBy('query')->orderByDesc('count')->limit(10)->get(),
            'topCategories' => DB::table('chatbot_events')
                ->join('products', 'products.id', '=', 'chatbot_events.product_id')
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->where('chatbot_events.type', 'product_shown')
                ->select('categories.name', DB::raw('COUNT(*) as count'))
                ->groupBy('categories.name')->orderByDesc('count')->limit(8)->get(),
        ]);
    }

    public function conversations(Request $request): Response
    {
        $conversations = ChatbotConversation::withCount('messages')->with('user:id,name', 'order:id,order_number')
            ->latest()->paginate(20)->through(fn (ChatbotConversation $c) => [
                'id' => $c->id,
                'user' => $c->user?->name ?? 'Guest',
                'messages_count' => $c->messages_count,
                'order' => $c->order?->order_number,
                'created_at' => $c->created_at->format('d M Y, h:i A'),
                'messages' => $c->messages()->orderBy('id')->get(['role', 'content', 'created_at'])
                    ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content]),
            ]);

        return Inertia::render('Admin/Chatbot/Conversations', ['conversations' => $conversations]);
    }
}
