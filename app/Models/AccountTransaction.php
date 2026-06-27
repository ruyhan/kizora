<?php

namespace App\Models;

use App\Services\Accounting\AccountingService;
use Illuminate\Database\Eloquent\Model;

class AccountTransaction extends Model
{
    protected $fillable = [
        'voucher_no', 'date', 'type', 'account_head', 'party', 'description',
        'payment_account', 'partner', 'cash_in', 'cash_out', 'vat_tax', 'discount',
        'status', 'remarks', 'import_batch_id',
    ];

    protected $casts = [
        'date'     => 'date',
        'cash_in'  => 'decimal:2',
        'cash_out' => 'decimal:2',
        'vat_tax'  => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    /** Only Posted transactions feed summaries/ledgers/dashboards. */
    public function scopePosted($query)
    {
        return $query->where('status', 'Posted');
    }

    public function getNetCashFlowAttribute(): float
    {
        return (float) $this->cash_in - (float) $this->cash_out;
    }

    /** Net cash flow, but only for profit-affecting transaction types. */
    public function getProfitImpactAttribute(): float
    {
        return AccountingService::affectsProfit($this->type) ? $this->net_cash_flow : 0.0;
    }
}
