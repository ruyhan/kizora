<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Additive only — no existing columns or data are touched.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('slug');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->date('sale_starts_at')->nullable()->after('discount_price');
            $table->date('sale_ends_at')->nullable()->after('sale_starts_at');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('is_approved');
            $table->timestamp('admin_reply_at')->nullable()->after('admin_reply');
        });
        // Orders already carry courier_name / tracking_number (2026_06_27 fulfilment migration).
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'sale_starts_at', 'sale_ends_at']);
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['admin_reply', 'admin_reply_at']);
        });
    }
};
