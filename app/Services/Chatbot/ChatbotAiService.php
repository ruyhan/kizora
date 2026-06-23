<?php

namespace App\Services\Chatbot;

use App\Models\Category;
use App\Support\ChatbotSettings;
use App\Support\DeliverySettings;
use App\Support\PageContent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Modular AI layer. Builds a context-aware prompt from LIVE site data
 * (products, categories, policies, delivery rules) and calls Groq or
 * OpenRouter. Falls back to a helpful templated reply when no key is set,
 * so the assistant keeps working (product search + order flow are DB-driven).
 */
class ChatbotAiService
{
    private const ENDPOINTS = [
        'groq'       => 'https://api.groq.com/openai/v1/chat/completions',
        'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
    ];

    public function __construct(
        private readonly ChatbotSettings $settings,
        private readonly DeliverySettings $delivery,
        private readonly PageContent $content,
    ) {}

    /**
     * @param  array  $history  [['role'=>'user'|'assistant','content'=>...], ...]
     * @param  Collection  $products  live products matched to the query (for grounding)
     */
    public function reply(string $message, array $history, Collection $products): string
    {
        $system = $this->systemPrompt($products);

        $messages = array_merge(
            [['role' => 'system', 'content' => $system]],
            array_slice($history, -8),
            [['role' => 'user', 'content' => $message]],
        );

        if ($this->settings->hasActiveKey()) {
            $reply = $this->callProvider($messages);
            if ($reply !== null) {
                return $reply;
            }
        }

        return $this->fallbackReply($message, $products);
    }

    private function systemPrompt(Collection $products): string
    {
        $site = $this->content->get('settings');
        $name = $this->settings->name();
        $store = $site['site_name'] ?? 'Kizora';

        $categories = Category::where('is_active', true)->pluck('name')->take(20)->implode(', ');

        $d = $this->delivery->publicConfig();
        $deliveryLine = $d['free_enabled']
            ? ($d['type'] === 'all' ? 'Free delivery on all orders.' : "Free delivery on orders above ৳{$d['minimum']}.")
            : "Delivery: ৳{$d['inside']} inside Dhaka, ৳{$d['outside']} outside Dhaka.";

        $faq = collect($this->content->get('faq')['items'] ?? [])
            ->take(6)->map(fn ($f) => "Q: {$f['question']} A: {$f['answer']}")->implode("\n");

        $productLines = $products->take(8)->map(function ($p) {
            return "- {$p->name} (৳" . (int) $p->final_price . ', ' . ($p->category?->name ?? 'general') . ')'
                . ($p->short_description ? ' — ' . \Illuminate\Support\Str::limit($p->short_description, 80) : '');
        })->implode("\n");

        return <<<PROMPT
You are {$name}, a friendly, concise AI sales & support assistant for {$store}, an e-commerce store.
Speak naturally and helpfully, like a great salesperson. Keep replies short (2–4 sentences).
You can recommend products, answer questions, and help the customer place an order.

Store categories: {$categories}
{$deliveryLine}

When relevant products are available, refer to them by name and encourage the customer; the UI shows
the product cards automatically, so don't paste long lists or prices repeatedly.
If the customer wants to buy/order, tell them to tap "Order" on a product card to start checkout.

Relevant products right now:
{$productLines}

Helpful FAQ:
{$faq}

Never invent products, prices, or policies that aren't provided here.
PROMPT;
    }

    private function callProvider(array $messages): ?string
    {
        $provider = $this->settings->provider();
        $endpoint = self::ENDPOINTS[$provider] ?? self::ENDPOINTS['groq'];

        try {
            $response = Http::withToken($this->settings->apiKey())
                ->timeout(20)
                ->acceptJson()
                ->post($endpoint, [
                    'model' => $this->settings->model(),
                    'messages' => $messages,
                    'temperature' => 0.6,
                    'max_tokens' => 400,
                ]);

            if ($response->successful()) {
                return trim((string) data_get($response->json(), 'choices.0.message.content', '')) ?: null;
            }
            Log::warning('Chatbot AI provider error', ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::warning('Chatbot AI call failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /** Deterministic reply used when no API key is configured or the call fails. */
    private function fallbackReply(string $message, Collection $products): string
    {
        $m = strtolower($message);

        if (str_contains($m, 'deliver') || str_contains($m, 'shipping')) {
            $d = $this->delivery->publicConfig();
            return $d['free_enabled']
                ? ($d['type'] === 'all' ? 'We offer free delivery on all orders! 🚚' : "Enjoy free delivery on orders above ৳{$d['minimum']}.")
                : "Delivery is ৳{$d['inside']} inside Dhaka and ৳{$d['outside']} outside Dhaka.";
        }
        if (str_contains($m, 'return') || str_contains($m, 'refund') || str_contains($m, 'warranty')) {
            return 'We offer easy returns within 7 days and a 1-year warranty on every Kizora product.';
        }
        if (preg_match('/\b(hi|hello|hey|salam|assalamu)\b/', $m)) {
            return 'Hello! 👋 Tell me what you\'re looking for — a backpack, bottle, or something else — and I\'ll find the best options for you.';
        }
        if ($products->isNotEmpty()) {
            return "Here are some great options I found for you 👇 Tap a product to view it, or tap \"Order\" to buy it right here.";
        }
        return 'I\'m here to help! You can ask about our products, delivery, returns, or say what you\'re shopping for and I\'ll recommend something.';
    }
}
