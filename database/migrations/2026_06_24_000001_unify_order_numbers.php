<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renumber every existing order to the canonical ORD-#### scheme and make each
 * invoice number match its order number exactly. Idempotent + safe (raw SQL,
 * no model events).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->orderBy('id')->select('id')->chunk(200, function ($orders) {
            foreach ($orders as $o) {
                $number = 'ORD-' . (10000 + $o->id);
                DB::table('orders')->where('id', $o->id)->update(['order_number' => $number]);
                DB::table('invoices')->where('order_id', $o->id)->update(['invoice_number' => $number]);
            }
        });
    }

    public function down(): void
    {
        // Renumbering is not reversed (original numbers were not retained).
    }
};
