<?php

namespace App\Http\Controllers;

use App\Models\ChatbotConversation;
use App\Models\ChatbotEvent;
use App\Models\ChatbotMessage;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\Chatbot\ChatbotAiService;
use App\Services\Chatbot\ProductRecommender;
use App\Support\ChatbotSettings;
use App\Support\DeliverySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatbotSettings $settings,
        private readonly ChatbotAiService $ai,
        private readonly ProductRecommender $recommender,
        private readonly DeliverySettings $delivery,
    ) {}

    private function conversation(Request $request): ChatbotConversation
    {
        $sid = $request->session()->get('chatbot_session');
        if (! $sid) {
            $sid = (string) \Illuminate\Support\Str::uuid();
            $request->session()->put('chatbot_session', $sid);
        }
        return ChatbotConversation::firstOrCreate(
            ['session_id' => $sid],
            ['user_id' => Auth::id()],
        );
    }

    public function message(Request $request)
    {
        if (! $this->settings->enabled()) {
            return response()->json(['error' => 'Chatbot is currently unavailable.'], 403);
        }

        $data = $request->validate(['message' => 'required|string|max:1000']);
        $conversation = $this->conversation($request);

        // Log user message
        ChatbotMessage::create(['conversation_id' => $conversation->id, 'role' => 'user', 'content' => $data['message'], 'created_at' => now()]);
        ChatbotEvent::track('query', ['conversation_id' => $conversation->id, 'query' => mb_substr($data['message'], 0, 191)]);

        // Live product recommendation
        $showProducts = $this->recommender->looksLikeProductQuery($data['message']);
        $products = $showProducts ? $this->recommender->search($data['message']) : collect();

        // Conversation history for context
        $history = $conversation->messages()->latest('id')->take(8)->get()
            ->reverse()->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])->values()->all();

        $reply = $this->ai->reply($data['message'], $history, $products);
        $cards = $products->isNotEmpty() ? $this->recommender->format($products) : [];

        ChatbotMessage::create([
            'conversation_id' => $conversation->id, 'role' => 'assistant', 'content' => $reply,
            'products' => collect($cards)->pluck('id')->all() ?: null, 'created_at' => now(),
        ]);
        foreach ($cards as $c) {
            ChatbotEvent::track('product_shown', ['conversation_id' => $conversation->id, 'product_id' => $c['id']]);
        }

        return response()->json(['reply' => $reply, 'products' => $cards]);
    }

    public function event(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:product_click',
            'product_id' => 'nullable|exists:products,id',
        ]);
        $conversation = $this->conversation($request);
        ChatbotEvent::track($data['type'], ['conversation_id' => $conversation->id, 'product_id' => $data['product_id'] ?? null]);
        return response()->json(['ok' => true]);
    }

    /** Active delivery cities + their areas, for the chatbot order form. */
    public function deliveryOptions()
    {
        $cities = DeliveryCity::active()
            ->with(['areas' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'areas' => $c->areas->map(fn ($a) => [
                    'id'   => $a->id,
                    'name' => $a->postal_code ? "{$a->name} ({$a->postal_code})" : $a->name,
                ])->values(),
            ])->values();

        return response()->json(['cities' => $cities]);
    }

    public function placeOrder(Request $request)
    {
        if (! $this->settings->enabled()) {
            return response()->json(['error' => 'Chatbot is currently unavailable.'], 403);
        }

        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1|max:50',
            'name' => 'required|string|max:255',
            'phone' => ['required', new \App\Rules\BdPhone],
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'delivery_area_id' => ['required', Rule::exists('delivery_areas', 'id')],
            'payment_method' => 'nullable|string|max:30',
        ], [
            'delivery_area_id.required' => 'Please choose your delivery city and area.',
        ]);

        // Delivery address must map to an ACTIVE delivery city.
        $area = DeliveryArea::with('city')->find($data['delivery_area_id']);
        if (! $area || ! $area->city || ! $area->city->is_active) {
            return response()->json(['error' => 'Sorry, we don\'t deliver to that city yet. Please pick an active delivery city.'], 422);
        }

        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
        foreach ($data['items'] as $line) {
            if ($line['quantity'] > $products[$line['product_id']]->stock) {
                return response()->json(['error' => "Sorry, {$products[$line['product_id']]->name} is out of stock."], 422);
            }
        }

        $subtotal = collect($data['items'])->sum(fn ($l) => (float) $products[$l['product_id']]->final_price * $l['quantity']);
        // Block the order if the chosen city has no delivery charge configured.
        $deliveryCharge = $this->delivery->chargeFor($area, $subtotal);
        if ($deliveryCharge === null) {
            return response()->json(['error' => $this->delivery->noChargeMessage()], 422);
        }
        $total = $subtotal + $deliveryCharge;
        $conversation = $this->conversation($request);

        $order = DB::transaction(function () use ($data, $products, $subtotal, $deliveryCharge, $total, $conversation, $area) {
            $order = Order::create([
                'source' => 'chatbot',
                'user_id' => Auth::id(),
                'guest_name' => Auth::check() ? null : $data['name'],
                'guest_phone' => Auth::check() ? null : $data['phone'],
                'guest_email' => Auth::check() ? null : ($data['email'] ?? null),
                'delivery_address' => $data['address'],
                'delivery_city' => $area->city->name,
                'delivery_area' => $area->name,
                'delivery_postal_code' => $area->postal_code,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_charge' => $deliveryCharge,
                'discount' => 0,
                'total' => $total,
                'payment_method' => $data['payment_method'] ?: 'cod',
                'payment_status' => 'pending',
                'notes' => 'Order placed via AI chatbot.',
            ]);

            foreach ($data['items'] as $line) {
                $p = $products[$line['product_id']];
                $price = (float) $p->final_price;
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $p->id, 'product_name' => $p->name,
                    'product_image' => $p->featured_image_url, 'price' => $price,
                    'quantity' => $line['quantity'], 'total' => $price * $line['quantity'],
                ]);
                $p->decrement('stock', $line['quantity']);
            }

            Invoice::forOrder($order);
            $conversation->update(['order_id' => $order->id]);
            ChatbotEvent::track('order', ['conversation_id' => $conversation->id, 'order_id' => $order->id]);

            return $order;
        });

        // Transactional emails (customer confirmation if an email is known + admin alert).
        try {
            $emails   = app(\App\Services\Email\EmailService::class);
            $settings = app(\App\Services\Email\EmailSettingService::class);
            $emails->send('order_placed_customer', $order->customer_email, $emails->orderVars($order), $order->id);
            foreach ($settings->adminRecipients() as $adminEmail) {
                $emails->send('order_received_admin', $adminEmail, $emails->adminVars($order), $order->id);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Chatbot order email failed: ' . $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'order_number' => $order->order_number,
            'total' => (float) $order->total,
            'delivery_charge' => (float) $order->delivery_charge,
        ]);
    }
}
