<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('template_key')->nullable()->index();
            $table->string('mailable')->nullable();
            $table->string('recipient')->index();
            $table->string('subject');
            $table->string('status')->default('queued')->index(); // queued | sent | failed
            $table->text('error')->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->json('variables')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
