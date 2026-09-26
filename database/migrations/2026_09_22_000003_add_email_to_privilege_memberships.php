<?php

use App\Models\PrivilegeMembership;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The email address becomes the identity of a membership, so admins can issue a
 * card before the customer has an account. `user_id` is therefore nullable until
 * someone signs up with that email and confirms it (claim_sent_at tracks the
 * activation email we send them).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privilege_memberships', function (Blueprint $table) {
            $table->string('name')->nullable()->after('user_id');            // holder name for cards issued by an admin
            $table->string('email')->nullable()->unique()->after('name');
            $table->timestamp('claim_sent_at')->nullable()->after('applied_at');
        });

        // Existing memberships take the email (and name) of the account they belong to.
        PrivilegeMembership::with('user')->whereNull('email')->each(function (PrivilegeMembership $m) {
            if ($m->user) {
                $m->forceFill(['email' => $m->user->email])->saveQuietly();
            }
        });

        Schema::table('privilege_memberships', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Memberships never claimed by an account can't exist without a user_id.
        PrivilegeMembership::whereNull('user_id')->delete();

        Schema::table('privilege_memberships', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->dropUnique(['email']);
            $table->dropColumn(['name', 'email', 'claim_sent_at']);
        });
    }
};
