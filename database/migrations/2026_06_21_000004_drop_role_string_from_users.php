<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the legacy `role` string column. Access control is now fully
 * driven by `role_id` + the roles/permissions tables. (Kept until here so
 * existing rows could be migrated into role_id first.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('role')->default('customer'));
    }
};
