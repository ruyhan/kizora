<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Old delivery_area_id references are no longer valid after the rebuild
        DB::table('users')->update(['delivery_area_id' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['delivery_area_id']);
        });

        Schema::dropIfExists('delivery_areas');

        // ── Cities (districts) — only these carry the enable/disable flag ──
        Schema::create('delivery_cities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // ── Areas (upazilas/thanas) — availability inherited from city ──
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_city_id')->constrained('delivery_cities')->cascadeOnDelete();
            $table->string('name');
            $table->string('postal_code', 20)->nullable();
            $table->timestamps();

            $table->unique(['delivery_city_id', 'name']);
        });

        // Re-link the user FK to the rebuilt areas table
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('delivery_area_id')->references('id')->on('delivery_areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('users')->update(['delivery_area_id' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['delivery_area_id']);
        });

        Schema::dropIfExists('delivery_areas');
        Schema::dropIfExists('delivery_cities');

        // Recreate the previous flat structure
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('city_name');
            $table->string('area_name');
            $table->string('postal_code', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['city_name', 'area_name']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('delivery_area_id')->references('id')->on('delivery_areas')->nullOnDelete();
        });
    }
};
