<?php

namespace App\Services\Accounting;

use App\Models\AccountTransaction;
use App\Models\WebsiteSetting;
use Illuminate\Support\Collection;

/**
 * Core accounting logic: configurable settings (partners, financial year, the
 * editable dropdown lists), profit classification, voucher numbering, and all
 * Posted-only aggregations used by the dashboard, summaries and ledgers.
 *
 * Convention: "performance" figures (sales, expenses, profit, monthly tables)
 * are scoped to the configured financial year; "balances" (cash/bank, ledger,
 * partner capital) are cumulative across all Posted transactions.
 */
class AccountingService
{
    /** Types that do NOT affect profit (capital/financing/transfers). */
    public const NON_PROFIT_TYPES = [
        'Partner Investment', 'Partner Drawing', 'Loan Received', 'Loan Repayment',
        'Asset Purchase', 'Bank Transfer', 'Opening Balance',
    ];

    /** Operating-expense style types (cash-out reduces profit). */
    public const OPERATING_TYPES = [
        'Operating Expense', 'Salary & Wages', 'Rent', 'Utilities',
        'Marketing Expense', 'Transport Expense', 'Expense',
    ];

    public static function affectsProfit(?string $type): bool
    {
        return $type !== null && ! in_array($type, self::NON_PROFIT_TYPES, true);
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function defaults(): array
    {
        return [
            'company'         => 'Kizora',
            'currency'        => 'BDT',
            'financial_year'  => (int) date('Y') >= 2026 ? (int) date('Y') : 2026,
            'start_date'      => '2026-01-01',
            'partners'        => [
                ['name' => 'Afsana', 'ownership' => 30, 'opening_capital' => 0],
                ['name' => 'Akib',   'ownership' => 70, 'opening_capital' => 0],
            ],
            'transaction_types' => [
                'Sales', 'Sales Return', 'Purchase', 'Purchase Return', 'Operating Expense',
                'Salary & Wages', 'Rent', 'Utilities', 'Marketing Expense', 'Transport Expense',
                'Other Income', 'Partner Investment', 'Partner Drawing', 'Loan Received',
                'Loan Repayment', 'Asset Purchase', 'Bank Transfer', 'Tax/VAT Payment',
                'Opening Balance', 'Expense',
            ],
            'account_heads' => [
                'Cash Sales', 'Credit Sales', 'Inventory Purchase', 'COGS/Stock Used',
                'Shop Rent', 'Employee Salary', 'Electricity Bill', 'Internet/Phone',
                'Office Supplies', 'Delivery/Transport', 'Marketing/Ads', 'Repair & Maintenance',
                'Packaging', 'Bank Charges', 'VAT/Tax', 'Equipment/Furniture', 'Other Income',
                'Miscellaneous', 'Charitable Expense',
            ],
            'payment_accounts' => ['Cash', 'Bank Account', 'Mobile Banking', 'Card/POS', 'Partner Account', 'Other'],
            'statuses'         => ['Posted', 'Draft', 'Pending', 'Cancelled'],
        ];
    }

    public function settings(): array
    {
        $saved = json_decode((string) WebsiteSetting::get('accounting_settings', ''), true) ?: [];
        return array_replace_recursive($this->defaults(), $saved);
    }

    public function saveSettings(array $data): void
    {
        // Keep only known keys merged over current settings.
        $merged = array_replace_recursive($this->settings(), array_intersect_key($data, $this->defaults()));
        WebsiteSetting::set('accounting_settings', json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function financialYear(): int
    {
        return (int) ($this->settings()['financial_year'] ?? date('Y'));
    }

    // ── Voucher numbering ──────────────────────────────────────────────────────

    public function nextVoucherNo(): string
    {
        $last = AccountTransaction::where('voucher_no', 'like', 'V-%')
            ->orderByRaw('CAST(SUBSTRING(voucher_no, 3) AS UNSIGNED) DESC')
            ->value('voucher_no');
        $n = $last ? ((int) substr($last, 2)) + 1 : 1;

        // Guard against collisions (e.g. imported custom vouchers).
        do {
            $candidate = 'V-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
            $n++;
        } while (AccountTransaction::where('voucher_no', $candidate)->exists());

        return $candidate;
    }

    // ── Aggregations ───────────────────────────────────────────────────────────

    private function sum(Collection $c, string $col): float
    {
        return (float) $c->sum(fn ($t) => (float) $t->{$col});
    }

    /** sum(cash_in − cash_out) for a set of types. */
    private function netForTypes(Collection $c, array $types): float
    {
        return (float) $c->whereIn('type', $types)->sum(fn ($t) => (float) $t->cash_in - (float) $t->cash_out);
    }

    private function inForTypes(Collection $c, array $types): float
    {
        return (float) $c->whereIn('type', $types)->sum(fn ($t) => (float) $t->cash_in);
    }

    private function outForTypes(Collection $c, array $types): float
    {
        return (float) $c->whereIn('type', $types)->sum(fn ($t) => (float) $t->cash_out);
    }

    private function estimatedProfit(Collection $c): float
    {
        return (float) $c->sum(fn ($t) => self::affectsProfit($t->type) ? ((float) $t->cash_in - (float) $t->cash_out) : 0);
    }

    public function allPosted(): Collection
    {
        return AccountTransaction::posted()->orderBy('date')->orderBy('id')->get();
    }

    public function fyPosted(?Collection $allPosted = null, ?int $year = null): Collection
    {
        $year ??= $this->financialYear();
        $all = $allPosted ?? $this->allPosted();
        return $all->filter(fn ($t) => (int) $t->date->format('Y') === $year)->values();
    }

    /** Dashboard KPIs + key metrics + 12-month snapshot. */
    public function dashboard(): array
    {
        $all = $this->allPosted();
        $fy  = $this->fyPosted($all);

        $cashIn  = $this->sum($all, 'cash_in');
        $cashOut = $this->sum($all, 'cash_out');

        return [
            'as_of'   => now()->format('d M Y, h:i A'),
            'year'    => $this->financialYear(),
            'kpis' => [
                'total_cash_in'  => round($cashIn, 2),
                'total_cash_out' => round($cashOut, 2),
                'balance'        => round($cashIn - $cashOut, 2),
                'estimated_profit' => round($this->estimatedProfit($fy), 2),
                'partner_capital'  => round(collect($this->partnerCapital()['rows'])->sum('current_capital'), 2),
            ],
            'metrics' => [
                'net_sales'           => round($this->netForTypes($fy, ['Sales', 'Sales Return']), 2),
                'total_purchases'     => round(-$this->netForTypes($fy, ['Purchase', 'Purchase Return']), 2),
                'operating_expenses'  => round($this->outForTypes($fy, self::OPERATING_TYPES), 2),
                'partner_investments' => round($this->inForTypes($fy, ['Partner Investment']), 2),
                'partner_drawings'    => round($this->outForTypes($fy, ['Partner Drawing']), 2),
                'vat_tax_paid'        => round($this->sum($fy, 'vat_tax'), 2),
            ],
            'monthly' => $this->monthlyRows($fy),
        ];
    }

    /** Per-month rows for the configured FY (used by Dashboard + Monthly Summary). */
    public function monthlyRows(Collection $fy): array
    {
        $byMonth = $fy->groupBy(fn ($t) => (int) $t->date->format('n'));
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $c = $byMonth->get($m, collect());
            $in  = $this->sum($c, 'cash_in');
            $out = $this->sum($c, 'cash_out');
            $rows[] = [
                'month'              => date('M', mktime(0, 0, 0, $m, 1)),
                'sales'              => round($this->inForTypes($c, ['Sales']), 2),
                'other_income'       => round($this->inForTypes($c, ['Other Income']), 2),
                'partner_investment' => round($this->inForTypes($c, ['Partner Investment']), 2),
                'purchases'          => round($this->outForTypes($c, ['Purchase']), 2),
                'operating_expenses' => round($this->outForTypes($c, self::OPERATING_TYPES), 2),
                'partner_drawings'   => round($this->outForTypes($c, ['Partner Drawing']), 2),
                'cash_in'            => round($in, 2),
                'cash_out'           => round($out, 2),
                'net_cash_flow'      => round($in - $out, 2),
                'profit'             => round($this->estimatedProfit($c), 2),
            ];
        }
        return $rows;
    }

    public function monthlySummary(): array
    {
        $fy = $this->fyPosted();
        $rows = $this->monthlyRows($fy);
        $total = [];
        foreach (['sales', 'other_income', 'partner_investment', 'purchases', 'operating_expenses', 'partner_drawings', 'cash_in', 'cash_out', 'net_cash_flow', 'profit'] as $k) {
            $total[$k] = round(array_sum(array_column($rows, $k)), 2);
        }

        return ['as_of' => now()->format('d M Y'), 'year' => $this->financialYear(), 'rows' => $rows, 'total' => $total];
    }

    public function partnerCapital(): array
    {
        $all = $this->allPosted();
        $profit = $this->estimatedProfit($this->fyPosted($all));
        $partners = $this->settings()['partners'];

        $rows = [];
        foreach ($partners as $p) {
            $name = $p['name'];
            $opening = (float) ($p['opening_capital'] ?? 0);
            $own = (float) ($p['ownership'] ?? 0);
            $invest = (float) $all->where('type', 'Partner Investment')->where('partner', $name)->sum(fn ($t) => (float) $t->cash_in);
            $draw   = (float) $all->where('type', 'Partner Drawing')->where('partner', $name)->sum(fn ($t) => (float) $t->cash_out);
            $current = $opening + $invest - $draw;
            $share = round($profit * $own / 100, 2);
            $rows[] = [
                'name' => $name,
                'ownership' => $own,
                'opening_capital' => round($opening, 2),
                'investment' => round($invest, 2),
                'drawings' => round($draw, 2),
                'current_capital' => round($current, 2),
                'profit_share' => $share,
                'capital_plus_profit' => round($current + $share, 2),
            ];
        }

        $total = [];
        foreach (['opening_capital', 'investment', 'drawings', 'current_capital', 'profit_share', 'capital_plus_profit'] as $k) {
            $total[$k] = round(array_sum(array_column($rows, $k)), 2);
        }
        $total['ownership'] = round(array_sum(array_column($rows, 'ownership')), 2);

        return ['as_of' => now()->format('d M Y'), 'rows' => $rows, 'total' => $total];
    }

    public function cashBank(): array
    {
        $all = $this->allPosted();
        $accounts = $this->settings()['payment_accounts'];
        $byAcct = $all->groupBy(fn ($t) => $t->payment_account ?: 'Other');

        $rows = [];
        foreach ($accounts as $acct) {
            $c = $byAcct->get($acct, collect());
            $in = $this->sum($c, 'cash_in');
            $out = $this->sum($c, 'cash_out');
            $rows[] = ['account' => $acct, 'cash_in' => round($in, 2), 'cash_out' => round($out, 2), 'balance' => round($in - $out, 2)];
        }

        $total = [
            'cash_in' => round(array_sum(array_column($rows, 'cash_in')), 2),
            'cash_out' => round(array_sum(array_column($rows, 'cash_out')), 2),
            'balance' => round(array_sum(array_column($rows, 'balance')), 2),
        ];

        return ['as_of' => now()->format('d M Y'), 'rows' => $rows, 'total' => $total];
    }

    public function ledger(): array
    {
        $all = $this->allPosted();
        $byHead = $all->groupBy(fn ($t) => $t->account_head ?: 'Miscellaneous');

        $rows = $byHead->map(function ($c, $head) {
            $in = $this->sum($c, 'cash_in');
            $out = $this->sum($c, 'cash_out');
            return [
                'account_head' => $head,
                'count' => $c->count(),
                'cash_in' => round($in, 2),
                'cash_out' => round($out, 2),
                'net' => round($in - $out, 2),
            ];
        })->sortBy('account_head')->values()->all();

        $total = [
            'count' => array_sum(array_column($rows, 'count')),
            'cash_in' => round(array_sum(array_column($rows, 'cash_in')), 2),
            'cash_out' => round(array_sum(array_column($rows, 'cash_out')), 2),
            'net' => round(array_sum(array_column($rows, 'net')), 2),
        ];

        return ['as_of' => now()->format('d M Y'), 'rows' => $rows, 'total' => $total];
    }
}
