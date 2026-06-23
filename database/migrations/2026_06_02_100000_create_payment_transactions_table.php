<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('gateway');                      // bkash | nagad | sslcommerz
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable(); // gateway's own reference
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('BDT');
            $table->json('gateway_response')->nullable();   // full raw response stored for audit
            $table->string('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // Add sslcommerz to orders payment_method and transaction reference
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_transaction_id')->nullable()->after('payment_status');
            // Widen the payment_method column to include sslcommerz
            $table->string('payment_method', 20)->default('cod')->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_transaction_id');
        });
    }
};
