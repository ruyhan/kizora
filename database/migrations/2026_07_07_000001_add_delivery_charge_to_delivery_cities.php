<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * City-wise delivery charges. Adds a per-city `delivery_charge` column and
 * seeds it from the previous Inside/Outside Dhaka settings so checkout behaves
 * identically the moment this runs:
 *   - a city named "Dhaka"  → the old inside-Dhaka charge
 *   - every other city      → the old outside-Dhaka charge
 * Additive only — no existing column is dropped or altered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_cities', function (Blueprint $table) {
            $table->decimal('delivery_charge', 10, 2)->nullable()->after('name');
        });

        // Seed from the old settings JSON so nothing changes for customers.
        $delivery = [];
        $path = base_path('data/delivery.json');
        if (is_file($path)) {
            $delivery = json_decode((string) file_get_contents($path), true) ?: [];
        }
        $inside  = isset($delivery['inside_dhaka_delivery_charge'])  ? (float) $delivery['inside_dhaka_delivery_charge']  : 80.0;
        $outside = isset($delivery['outside_dhaka_delivery_charge']) ? (float) $delivery['outside_dhaka_delivery_charge'] : 150.0;

        foreach (DB::table('delivery_cities')->get(['id', 'name']) as $city) {
            $charge = strcasecmp(trim((string) $city->name), 'Dhaka') === 0 ? $inside : $outside;
            DB::table('delivery_cities')->where('id', $city->id)->update(['delivery_charge' => $charge]);
        }
    }

    public function down(): void
    {
        Schema::table('delivery_cities', function (Blueprint $table) {
            $table->dropColumn('delivery_charge');
        });
    }
};
