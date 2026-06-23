<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        Schema::create('corporate_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 20);
            $table->string('event_type')->nullable();
            $table->integer('expected_quantity')->nullable();
            $table->date('required_date')->nullable();
            $table->text('requirements')->nullable();
            $table->enum('status', ['new', 'contacted', 'quoted', 'confirmed', 'cancelled'])->default('new');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_inquiries');
        Schema::dropIfExists('contact_messages');
    }
};
