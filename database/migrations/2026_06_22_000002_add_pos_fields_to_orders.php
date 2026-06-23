<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mark order origin (web | pos) and widen payment_method so POS payment
 * types (cash, card, mobile_banking, bank_transfer, other) are allowed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source')->default('web')->after('order_number'); // web | pos
        });

        // enum → varchar so POS payment methods are accepted (raw SQL avoids needing doctrine/dbal)
        DB::statement("ALTER TABLE orders MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT 'cod'");
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('source'));
        DB::statement("ALTER TABLE orders MODIFY payment_method ENUM('cod','bkash','nagad') NOT NULL DEFAULT 'cod'");
    }
};
