<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // registry key, e.g. order_placed_customer
            $table->string('name');
            $table->string('subject');
            $table->longText('body_html');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_custom')->default(false); // duplicated/custom (not in the registry)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
