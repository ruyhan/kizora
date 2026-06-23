<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\Invoice\InvoiceService;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoices) {}

    public function show(Order $order)
    {
        AuditLog::record('viewed', 'Invoice', "Viewed invoice for {$order->order_number}");
        return $this->invoices->view($order);
    }

    public function print(Order $order)
    {
        AuditLog::record('printed', 'Invoice', "Printed invoice for {$order->order_number}");
        return $this->invoices->view($order, print: true);
    }

    public function download(Order $order)
    {
        AuditLog::record('downloaded', 'Invoice', "Downloaded invoice for {$order->order_number}");
        return $this->invoices->pdf($order)->download($this->invoices->filename($order));
    }

    public function regenerate(Order $order)
    {
        $invoice = $this->invoices->regenerate($order);
        AuditLog::record('regenerated', 'Invoice', "Regenerated invoice {$invoice->invoice_number} for {$order->order_number}");
        return back()->with('success', 'Invoice regenerated.');
    }
}
