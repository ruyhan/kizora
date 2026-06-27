<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('seen_at')->nullable()->after('is_approved');
        });

        // Treat all existing reviews as already seen so the badge only counts new ones.
        DB::table('reviews')->update(['seen_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('seen_at');
        });
    }
};
