<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ChatbotSettings;
use App\Support\PageContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class ChatbotSettingsController extends Controller
{
    public function __construct(
        private readonly PageContent $content,
        private readonly ChatbotSettings $chatbot,
    ) {}

    public function index()
    {
        $d = $this->content->get('chatbot');
        // Never expose raw keys to the client.
        $d['groq_api_key'] = '';
        $d['openrouter_api_key'] = '';

        return Inertia::render('Admin/ChatbotSettings/Index', [
            'settings' => $d,
            'hasGroqKey' => $this->chatbot->hasKey('groq'),
            'hasOpenrouterKey' => $this->chatbot->hasKey('openrouter'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'boolean',
            'provider' => 'required|in:groq,openrouter',
            'groq_api_key' => 'nullable|string|max:300',
            'openrouter_api_key' => 'nullable|string|max:300',
            'model' => 'nullable|string|max:120',
            'name' => 'nullable|string|max:120',
            'welcome_message' => 'nullable|string|max:1000',
            'avatar' => 'nullable|string|max:1000',
            'widget_icon' => 'nullable|string|max:1000',
            'primary_color' => 'nullable|string|max:30',
            'position' => 'required|in:bottom-right,bottom-left',
            'theme' => 'required|in:light,dark',
            'rounded' => 'boolean',
        ]);

        $existing = $this->content->get('chatbot');

        // Keys: encrypt new values, otherwise keep what's already stored.
        foreach (['groq_api_key', 'openrouter_api_key'] as $k) {
            $data[$k] = ! empty($data[$k]) ? Crypt::encryptString(trim($data[$k])) : ($existing[$k] ?? '');
        }

        $this->content->save('chatbot', $data);
        AuditLog::record('updated', 'Chatbot Settings', 'Updated chatbot configuration');

        return back()->with('success', 'Chatbot settings saved.');
    }
}
