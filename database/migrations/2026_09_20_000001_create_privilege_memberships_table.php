<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Privilege Membership: one row per customer application. The card number
 * (prefix + zero-padded serial) is assigned once, on first approval, and
 * kept for life — re-approving a revoked member restores the same card.
 *
 * Also registers the membership permissions and grants them to super_admin /
 * admin only (additively, like the blog permission migration).
 */
return new class extends Migration
{
    private array $perms = [
        'membership.view'     => 'View Privilege Members',
        'membership.manage'   => 'Approve / Reject Privilege Members',
        'membership.settings' => 'Edit Privilege Card Settings',
    ];

    public function up(): void
    {
        Schema::create('privilege_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->index(); // pending|approved|rejected|revoked
            $table->string('phone', 30)->nullable();
            $table->text('note')->nullable();              // applicant's message
            $table->string('card_number', 32)->nullable()->unique();
            $table->unsignedInteger('serial')->nullable()->unique();
            $table->text('admin_note')->nullable();        // rejection / revoke reason shown to the customer
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $ids = [];
        foreach ($this->perms as $slug => $label) {
            $ids[] = Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $label, 'module' => 'Privilege Membership']
            )->id;
        }
        foreach (['super_admin', 'admin'] as $roleSlug) {
            Role::where('slug', $roleSlug)->first()?->permissions()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('privilege_memberships');
        Permission::whereIn('slug', array_keys($this->perms))->delete();
    }
};
