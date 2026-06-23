<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('city_name');
            $table->string('area_name');
            $table->string('postal_code', 20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['city_name', 'area_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_areas');
    }
};
