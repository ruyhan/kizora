<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['order_id', 'invoice_number'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get (or create) the order's invoice. The invoice number is always the
     * exact order number — single source of truth, no separate generator.
     */
    public static function forOrder(Order $order): self
    {
        $invoice = static::firstOrCreate(
            ['order_id' => $order->id],
            ['invoice_number' => $order->order_number],
        );

        // Keep in sync if the order number ever differs (e.g. legacy data).
        if ($invoice->invoice_number !== $order->order_number) {
            $invoice->update(['invoice_number' => $order->order_number]);
        }

        return $invoice;
    }
}
