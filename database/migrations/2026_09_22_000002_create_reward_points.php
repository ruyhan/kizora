<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reward points for customers without a Privilege Card: earned on completed
 * orders, redeemed at checkout. `users.reward_points` is the live balance; every
 * change is written to `point_transactions` so the balance can always be audited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('reward_points')->default(0);
        });

        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);                 // earn | redeem | refund | reverse | adjust
            $table->integer('points');                  // signed: + credit, − debit
            $table->unsignedInteger('balance_after');
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['order_id', 'type']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedInteger('points_redeemed')->default(0)->after('privilege_card_number');
            $table->decimal('points_discount', 10, 2)->default(0)->after('points_redeemed');
            $table->unsignedInteger('points_earned')->default(0)->after('points_discount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['points_redeemed', 'points_discount', 'points_earned']);
        });
        Schema::dropIfExists('point_transactions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reward_points');
        });
    }
};
