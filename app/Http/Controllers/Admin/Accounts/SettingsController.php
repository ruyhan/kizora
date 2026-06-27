<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly AccountingService $acc) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Accounts/Settings', [
            'settings' => $this->acc->settings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'company'        => 'nullable|string|max:255',
            'currency'       => 'nullable|string|max:10',
            'financial_year' => 'required|integer|min:2000|max:2100',
            'start_date'     => 'nullable|date',
            'partners'                 => 'array|max:6',
            'partners.*.name'          => 'required|string|max:120',
            'partners.*.ownership'     => 'required|numeric|min:0|max:100',
            'partners.*.opening_capital' => 'nullable|numeric',
            'transaction_types'   => 'array',
            'transaction_types.*' => 'string|max:80',
            'account_heads'       => 'array',
            'account_heads.*'     => 'string|max:120',
            'payment_accounts'    => 'array',
            'payment_accounts.*'  => 'string|max:80',
            'statuses'            => 'array',
            'statuses.*'          => 'string|max:40',
        ]);

        // Normalise partner numbers.
        $data['partners'] = collect($data['partners'] ?? [])->map(fn ($p) => [
            'name' => $p['name'],
            'ownership' => (float) $p['ownership'],
            'opening_capital' => (float) ($p['opening_capital'] ?? 0),
        ])->all();

        $this->acc->saveSettings($data);
        AuditLog::record('updated', 'Accounts', 'Updated accounting settings');

        return back()->with('success', 'Accounting settings saved.');
    }
}
