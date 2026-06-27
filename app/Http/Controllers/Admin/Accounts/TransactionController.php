<?php

namespace App\Http\Controllers\Admin\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountTransaction;
use App\Services\Accounting\AccountingService;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransactionController extends Controller
{
    public function __construct(private readonly AccountingService $acc) {}

    public function index(Request $request)
    {
        $dr = DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(AccountTransaction::query(), 'date')->orderBy('date', 'desc')->orderByDesc('id');

        foreach (['type', 'account_head', 'partner', 'status', 'payment_account'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('party', 'like', "%{$s}%")
                ->orWhere('description', 'like', "%{$s}%")
                ->orWhere('remarks', 'like', "%{$s}%")
                ->orWhere('voucher_no', 'like', "%{$s}%"));
        }

        // Running balance across ALL posted rows in date order.
        $balances = [];
        $run = 0;
        foreach (AccountTransaction::posted()->orderBy('date')->orderBy('id')->get(['id', 'cash_in', 'cash_out']) as $t) {
            $run += (float) $t->cash_in - (float) $t->cash_out;
            $balances[$t->id] = round($run, 2);
        }

        $transactions = $query->paginate(25)->withQueryString()->through(fn (AccountTransaction $t) => [
            'id' => $t->id,
            'voucher_no' => $t->voucher_no,
            'date' => $t->date->format('Y-m-d'),
            'type' => $t->type,
            'account_head' => $t->account_head,
            'party' => $t->party,
            'description' => $t->description,
            'payment_account' => $t->payment_account,
            'partner' => $t->partner,
            'cash_in' => (float) $t->cash_in,
            'cash_out' => (float) $t->cash_out,
            'vat_tax' => (float) $t->vat_tax,
            'discount' => (float) $t->discount,
            'status' => $t->status,
            'remarks' => $t->remarks,
            'net_cash_flow' => $t->net_cash_flow,
            'profit_impact' => $t->profit_impact,
            'running_balance' => $balances[$t->id] ?? null,
        ]);

        $settings = $this->acc->settings();

        return \Inertia\Inertia::render('Admin/Accounts/Transactions', [
            'transactions' => $transactions,
            'filters' => array_merge($request->only(['type', 'account_head', 'partner', 'status', 'payment_account', 'search']), $dr->filters()),
            'lists' => [
                'transaction_types' => $settings['transaction_types'],
                'account_heads' => $settings['account_heads'],
                'payment_accounts' => $settings['payment_accounts'],
                'statuses' => $settings['statuses'],
            ],
            'partners' => array_column($settings['partners'], 'name'),
            'currency' => $settings['currency'],
            'nextVoucher' => $this->acc->nextVoucherNo(),
            'canManage' => $request->user()->hasPermission('accounting.manage'),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'date' => 'required|date',
            'type' => 'required|string|max:80',
            'account_head' => 'nullable|string|max:120',
            'party' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'payment_account' => 'nullable|string|max:80',
            'partner' => 'nullable|string|max:120',
            'cash_in' => 'nullable|numeric|min:0',
            'cash_out' => 'nullable|numeric|min:0',
            'vat_tax' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $in = (float) ($data['cash_in'] ?? 0);
        $out = (float) ($data['cash_out'] ?? 0);
        if ($in <= 0 && $out <= 0) {
            throw ValidationException::withMessages(['cash_in' => 'Enter a Cash In or Cash Out amount.']);
        }
        if ($in > 0 && $out > 0) {
            throw ValidationException::withMessages(['cash_out' => 'A transaction cannot have both Cash In and Cash Out.']);
        }
        if (in_array($data['type'], ['Partner Investment', 'Partner Drawing'], true) && empty($data['partner'])) {
            throw ValidationException::withMessages(['partner' => 'Partner is required for ' . $data['type'] . '.']);
        }

        $data['cash_in'] = $in;
        $data['cash_out'] = $out;
        $data['vat_tax'] = (float) ($data['vat_tax'] ?? 0);
        $data['discount'] = (float) ($data['discount'] ?? 0);

        return $data;
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['voucher_no'] = $this->acc->nextVoucherNo();
        AccountTransaction::create($data);

        return back()->with('success', 'Transaction saved.');
    }

    public function update(Request $request, AccountTransaction $transaction)
    {
        $transaction->update($this->validated($request));

        return back()->with('success', 'Transaction updated.');
    }

    public function destroy(AccountTransaction $transaction)
    {
        $transaction->delete();

        return back()->with('success', 'Transaction deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        AccountTransaction::whereIn('id', $data['ids'])->delete();

        return back()->with('success', count($data['ids']) . ' transaction(s) deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        $dr = DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(AccountTransaction::query(), 'date')->orderBy('date');
        foreach (['type', 'account_head', 'partner', 'status', 'payment_account'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->get($f));
            }
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Voucher No', 'Type', 'Account Head', 'Party', 'Description', 'Payment Account', 'Partner', 'Cash In', 'Cash Out', 'VAT/Tax', 'Discount', 'Status', 'Remarks']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $t) {
                    fputcsv($out, [
                        $t->date->format('Y-m-d'), $t->voucher_no, $t->type, $t->account_head, $t->party,
                        $t->description, $t->payment_account, $t->partner, $t->cash_in, $t->cash_out,
                        $t->vat_tax, $t->discount, $t->status, $t->remarks,
                    ]);
                }
            });
            fclose($out);
        }, 'transactions-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
