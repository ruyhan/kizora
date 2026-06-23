<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;

/**
 * Chatbot configuration accessor. API keys are stored encrypted and never
 * exposed to the frontend — only UI-safe fields go through publicConfig().
 */
class ChatbotSettings
{
    public function __construct(private readonly PageContent $content) {}

    private function data(): array
    {
        return $this->content->get('chatbot');
    }

    public function enabled(): bool { return (bool) ($this->data()['enabled'] ?? false); }
    public function provider(): string { return $this->data()['provider'] ?? 'groq'; }
    public function model(): string { return $this->data()['model'] ?? 'llama-3.3-70b-versatile'; }
    public function name(): string { return $this->data()['name'] ?? 'Assistant'; }

    /** Decrypted API key for the active provider (empty string if unset/invalid). */
    public function apiKey(): string
    {
        $key = $this->provider() === 'openrouter'
            ? ($this->data()['openrouter_api_key'] ?? '')
            : ($this->data()['groq_api_key'] ?? '');
        return $this->decrypt($key);
    }

    public function hasKey(string $which): bool
    {
        return (bool) trim($this->decrypt($this->data()[$which . '_api_key'] ?? ''));
    }

    public function hasActiveKey(): bool
    {
        return (bool) trim($this->apiKey());
    }

    private function decrypt(?string $value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return ''; // not encrypted / corrupt
        }
    }

    /** Only fields safe to expose to the public widget. */
    public function publicConfig(): array
    {
        $d = $this->data();
        return [
            'enabled'         => (bool) ($d['enabled'] ?? false),
            'name'            => $d['name'] ?? 'Assistant',
            'welcome_message' => $d['welcome_message'] ?? 'Hi! How can I help?',
            'avatar'          => $d['avatar'] ?? '',
            'widget_icon'     => $d['widget_icon'] ?? '',
            'primary_color'   => $d['primary_color'] ?? '#17181B',
            'position'        => $d['position'] ?? 'bottom-right',
            'theme'           => $d['theme'] ?? 'light',
            'rounded'         => (bool) ($d['rounded'] ?? true),
        ];
    }
}
