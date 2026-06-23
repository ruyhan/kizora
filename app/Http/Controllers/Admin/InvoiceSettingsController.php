<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\PageContent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceSettingsController extends Controller
{
    public function __construct(private readonly PageContent $content) {}

    public function index()
    {
        return Inertia::render('Admin/InvoiceSettings/Index', [
            'settings' => $this->content->get('invoice'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company_logo'    => 'nullable|string|max:1000',
            'company_name'    => 'nullable|string|max:255',
            'company_address' => 'nullable|string|max:1000',
            'company_phone'   => 'nullable|string|max:100',
            'company_email'   => 'nullable|string|max:255',
            'company_website' => 'nullable|string|max:255',
            'invoice_title'   => 'nullable|string|max:100',
            'header_text'     => 'nullable|string|max:255',
            'footer_message'  => 'nullable|string|max:1000',
            'notes'           => 'nullable|string|max:2000',
            'terms'           => 'nullable|string|max:2000',
            'primary_color'   => 'nullable|string|max:30',
            'secondary_color' => 'nullable|string|max:30',
        ]);

        $this->content->save('invoice', $data);
        AuditLog::record('updated', 'Invoice Settings', 'Updated invoice branding & content');

        return back()->with('success', 'Invoice settings saved.');
    }
}
