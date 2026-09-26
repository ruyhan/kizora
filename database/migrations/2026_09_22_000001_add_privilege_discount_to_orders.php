<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Privilege Member discount snapshot on the order. `discount` stays the total
 * discount (coupon + member) so every existing total/invoice view keeps working;
 * these columns record how much of it came from membership and which card was used.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('member_discount', 10, 2)->default(0)->after('discount');
            $table->string('privilege_card_number', 30)->nullable()->after('member_discount')->index();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['privilege_card_number']);
            $table->dropColumn(['member_discount', 'privilege_card_number']);
        });
    }
};
