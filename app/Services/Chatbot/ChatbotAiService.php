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
     * Ping the AI provider with a tiny prompt to confirm the key/model/endpoint
     * all work. Accepts an explicit (possibly unsaved) key/model so the admin can
     * test before saving.
     *
     * @return array{ok:bool,message:string,provider?:string,model?:string,reply?:string}
     */
    public function testConnection(string $provider, string $apiKey, ?string $model = null): array
    {
        $provider = isset(self::ENDPOINTS[$provider]) ? $provider : 'groq';
        $endpoint = self::ENDPOINTS[$provider];
        $model = $model ?: $this->settings->model();
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            return ['ok' => false, 'message' => "No API key set for {$provider}. Enter a key (or save one) and try again."];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->acceptJson()
                ->post($endpoint, [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => 'Reply with exactly the word: pong']],
                    'temperature' => 0,
                    'max_tokens' => 5,
                ]);

            if ($response->successful()) {
                $reply = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

                return [
                    'ok'       => true,
                    'message'  => "Connected to {$provider} · model “{$model}” responded successfully.",
                    'provider' => $provider,
                    'model'    => $model,
                    'reply'    => $reply,
                ];
            }

            $err = data_get($response->json(), 'error.message') ?: $response->body();
            $hint = match ($response->status()) {
                401, 403 => 'Check the API key.',
                404      => 'Check the model id is valid for this provider.',
                429      => 'Rate limited / out of quota.',
                default  => '',
            };

            return [
                'ok'      => false,
                'message' => "HTTP {$response->status()}: " . \Illuminate\Support\Str::limit((string) $err, 240) . ($hint ? " ({$hint})" : ''),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
        }
    }

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

        $deliveryLine = $this->delivery->chargeSummary();

        $faq = collect($this->content->get('faq')['items'] ?? [])
            ->take(6)->map(fn ($f) => "Q: {$f['question']} A: {$f['answer']}")->implode("\n");

        $productLines = $products->take(8)->map(function ($p) {
            return "- {$p->name} (৳" . (int) $p->final_price . ', ' . ($p->category?->name ?? 'general') . ')'
                . ($p->short_description ? ' — ' . \Illuminate\Support\Str::limit($p->short_description, 80) : '');
        })->implode("\n");

        return <<<PROMPT
You are {$name}, the friendly, human-like shopping assistant for {$store}.

How you talk:
- Warm, natural and genuinely helpful — like a thoughtful salesperson who actually listens, not a robot.
- Acknowledge what the customer said before you answer, and show a little personality and empathy.
- Keep messages short and easy (2–4 sentences). Ask only ONE question at a time.
- Mirror the customer's language and tone — if they write in Bangla or Banglish, reply the same way.
- Use the customer's name once you know it. Never sound scripted or dump long bulleted lists.

What you can help with:
- Recommend products from our LIVE catalogue, and answer questions about products, delivery and policies.
- Help them buy. To place an order the customer taps "Order" on a product card — a short form opens to
  collect their name, phone, delivery city/area and address. Warmly guide and reassure them through it,
  and confirm the key details (item, quantity, delivery city) before they submit.

Store categories: {$categories}
{$deliveryLine}

Relevant products right now (shown to the customer as cards — refer to them by name, don't repeat prices or paste lists):
{$productLines}

Helpful FAQ:
{$faq}

Be honest: only use the products, prices, stock and policies provided here. If you're unsure or it's not
covered, say so kindly and offer to connect them with our support team. Never invent details.
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
            return $this->delivery->chargeSummary();
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
