<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->date('date')->index();
            $table->string('type')->index();
            $table->string('account_head')->nullable()->index();
            $table->string('party')->nullable();
            $table->string('description')->nullable();
            $table->string('payment_account')->nullable()->index();
            $table->string('partner')->nullable();
            $table->decimal('cash_in', 14, 2)->default(0);
            $table->decimal('cash_out', 14, 2)->default(0);
            $table->decimal('vat_tax', 14, 2)->default(0);
            $table->decimal('discount', 14, 2)->default(0);
            $table->string('status')->default('Draft')->index();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('import_batch_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transactions');
    }
};
