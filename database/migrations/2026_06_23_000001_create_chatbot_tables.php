<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // if it converted
            $table->timestamps();
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->string('role'); // user | assistant
            $table->text('content');
            $table->json('products')->nullable(); // recommended product ids shown with this message
            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
        });

        // Lightweight analytics events: product_shown | product_click | order
        Schema::create('chatbot_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained('chatbot_conversations')->nullOnDelete();
            $table->string('type')->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('query')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['type', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_events');
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
    }
};
