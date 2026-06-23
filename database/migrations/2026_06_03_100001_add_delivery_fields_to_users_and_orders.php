<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('delivery_area_id')->nullable()->after('phone')
                ->constrained('delivery_areas')->nullOnDelete();
            $table->text('delivery_address')->nullable()->after('delivery_area_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Snapshot of the area + postal used at order time
            $table->string('delivery_area')->nullable()->after('delivery_city');
            $table->string('delivery_postal_code', 20)->nullable()->after('delivery_area');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['delivery_area_id']);
            $table->dropColumn(['delivery_area_id', 'delivery_address']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_area', 'delivery_postal_code']);
        });
    }
};
