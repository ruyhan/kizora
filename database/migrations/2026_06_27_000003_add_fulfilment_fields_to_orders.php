<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier_name')->nullable()->after('notes');
            $table->string('tracking_number')->nullable()->after('courier_name');
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->date('estimated_delivery_date')->nullable()->after('tracking_url');
            $table->string('cancel_reason')->nullable()->after('estimated_delivery_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_name', 'tracking_number', 'tracking_url', 'estimated_delivery_date', 'cancel_reason']);
        });
    }
};
