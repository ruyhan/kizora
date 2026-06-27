<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Services\Accounting\AccountingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SummaryController extends Controller
{
    public function __construct(private readonly AccountingService $acc) {}

    public function monthly(): Response
    {
        return Inertia::render('Admin/Accounts/Monthly', [
            'data'     => $this->acc->monthlySummary(),
            'currency' => $this->acc->settings()['currency'],
        ]);
    }

    public function partnerCapital(): Response
    {
        return Inertia::render('Admin/Accounts/PartnerCapital', [
            'data'     => $this->acc->partnerCapital(),
            'currency' => $this->acc->settings()['currency'],
            'year'     => $this->acc->financialYear(),
        ]);
    }

    public function cashBank(): Response
    {
        return Inertia::render('Admin/Accounts/CashBank', [
            'data'     => $this->acc->cashBank(),
            'currency' => $this->acc->settings()['currency'],
        ]);
    }

    public function ledger(): Response
    {
        return Inertia::render('Admin/Accounts/Ledger', [
            'data'     => $this->acc->ledger(),
            'currency' => $this->acc->settings()['currency'],
        ]);
    }

    /** Drill-down: all Posted transactions for one account head (JSON). */
    public function ledgerHead(Request $request)
    {
        $head = (string) $request->query('head', '');
        $rows = AccountTransaction::posted()
            ->where('account_head', $head)
            ->orderBy('date')
            ->get(['id', 'date', 'voucher_no', 'type', 'party', 'description', 'cash_in', 'cash_out'])
            ->map(fn ($t) => [
                'date' => $t->date->format('Y-m-d'),
                'voucher_no' => $t->voucher_no,
                'type' => $t->type,
                'party' => $t->party,
                'description' => $t->description,
                'cash_in' => (float) $t->cash_in,
                'cash_out' => (float) $t->cash_out,
            ]);

        return response()->json(['head' => $head, 'rows' => $rows]);
    }
}
