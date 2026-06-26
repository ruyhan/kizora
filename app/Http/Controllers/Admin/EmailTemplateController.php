<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\Email\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function __construct(private readonly EmailService $emails) {}

    public function index()
    {
        $this->emails->ensureSeeded();
        $registry = $this->emails->registry();

        $templates = EmailTemplate::orderBy('is_custom')->orderBy('id')->get()->map(fn ($t) => [
            'id'         => $t->id,
            'key'        => $t->key,
            'name'       => $t->name,
            'subject'    => $t->subject,
            'group'      => $registry[$t->key]['group'] ?? 'Custom',
            'is_enabled' => $t->is_enabled,
            'is_custom'  => $t->is_custom,
        ]);

        return Inertia::render('Admin/Email/Templates', ['templates' => $templates]);
    }

    public function edit(EmailTemplate $template)
    {
        return Inertia::render('Admin/Email/TemplateEdit', [
            'template'  => [
                'id'         => $template->id,
                'key'        => $template->key,
                'name'       => $template->name,
                'subject'    => $template->subject,
                'body_html'  => $template->body_html,
                'is_enabled' => $template->is_enabled,
                'is_custom'  => $template->is_custom,
            ],
            'variables' => $this->emails->variablesFor($template->key),
        ]);
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'subject'    => 'required|string|max:255',
            'body_html'  => 'required|string',
            'is_enabled' => 'boolean',
        ]);
        $template->update($data);

        return back()->with('success', 'Template saved.');
    }

    /** Render the (possibly unsaved) template with sample data for the live iframe. */
    public function preview(Request $request, EmailTemplate $template)
    {
        $data = $request->validate([
            'subject'   => 'nullable|string',
            'body_html' => 'nullable|string',
        ]);

        $html = $this->emails->renderPreview(
            $data['subject'] ?? $template->subject,
            $data['body_html'] ?? $template->body_html,
            $this->emails->sampleVars(),
        );

        return response()->json(['html' => $html]);
    }

    public function test(Request $request, EmailTemplate $template)
    {
        $request->validate(['to' => 'required|email']);
        $result = $this->emails->sendTest($template->key, $request->to);

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function reset(EmailTemplate $template)
    {
        $this->emails->reset($template->key);

        return back()->with('success', 'Template reset to default.');
    }

    public function duplicate(EmailTemplate $template)
    {
        $copy = $this->emails->duplicate($template->key);

        return redirect()
            ->route('admin.email.templates.edit', $copy->id)
            ->with('success', 'Template duplicated.');
    }

    public function toggle(EmailTemplate $template)
    {
        $template->update(['is_enabled' => ! $template->is_enabled]);

        return back()->with('success', $template->is_enabled ? 'Template enabled.' : 'Template disabled.');
    }

    public function destroy(EmailTemplate $template)
    {
        if (! $template->is_custom) {
            return back()->with('error', 'Built-in templates cannot be deleted (use Reset instead).');
        }
        $template->delete();

        return redirect()->route('admin.email.templates')->with('success', 'Template deleted.');
    }
}
