<?php

namespace Tests\Feature;

use App\Models\PrivilegeMembership;
use App\Models\Role;
use App\Models\User;
use App\Services\PrivilegeClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Admin-issued memberships: created against an email, claimed once that address is confirmed. */
class PrivilegeEmailMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_staff' => true]);
        return User::factory()->create(['status' => 'active', 'role_id' => $role->id]);
    }

    private function issue(string $email = 'rahim@example.com'): PrivilegeMembership
    {
        $this->actingAs($this->superAdmin())->post(route('admin.privilege.store'), [
            'name' => 'Rahim Uddin', 'email' => $email, 'phone' => '01712345678',
        ])->assertSessionHasNoErrors();

        return PrivilegeMembership::where('email', $email)->firstOrFail();
    }

    public function test_admin_issues_a_card_for_an_email_without_an_account(): void
    {
        $m = $this->issue();

        $this->assertSame('approved', $m->status);
        $this->assertNotNull($m->card_number);
        $this->assertNull($m->user_id);                 // waiting for its owner
        $this->assertSame('Rahim Uddin', $m->holder_name);
    }

    public function test_one_membership_per_email(): void
    {
        $this->issue();

        $this->actingAs($this->superAdmin())->post(route('admin.privilege.store'), [
            'name' => 'Someone Else', 'email' => 'rahim@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, PrivilegeMembership::count());
    }

    public function test_admin_issued_card_lands_in_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'rahim@example.com', 'status' => 'active', 'role_id' => null]);
        $m = $this->issue();

        $this->assertSame($user->id, $m->user_id);
        $this->assertTrue($user->fresh()->privilegeMembership->isApproved());
    }

    public function test_signing_up_does_not_hand_over_the_card_until_the_email_is_confirmed(): void
    {
        $m = $this->issue();

        $user = User::create([
            'name' => 'Imposter', 'email' => 'rahim@example.com', 'password' => Hash::make('password-123'),
            'status' => 'active', 'role_id' => null,
        ]);

        $this->assertNull($m->fresh()->user_id);                    // not linked
        $this->assertNotNull($m->fresh()->claim_sent_at);           // activation link emailed
        $this->assertNull($user->fresh()->privilegeMembership);

        // The customer's own page shows it waiting, not as an active card.
        $this->actingAs($user)->get(route('account.privilege'))
            ->assertInertia(fn ($page) => $page->where('awaitingActivation.email', 'rahim@example.com')->where('membership', null));
    }

    public function test_activation_link_hands_over_the_card(): void
    {
        $m = $this->issue();
        $user = User::create([
            'name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'password' => Hash::make('password-123'),
            'status' => 'active', 'role_id' => null,
        ]);

        $this->actingAs($user)->get(app(PrivilegeClaimService::class)->linkFor($m->fresh()))
            ->assertRedirect(route('account.privilege'));

        $this->assertSame($user->id, $m->fresh()->user_id);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue($user->fresh()->privilegeMembership->isApproved());
    }

    public function test_activation_link_of_another_email_is_refused(): void
    {
        $m = $this->issue();
        $other = User::factory()->create(['email' => 'karim@example.com', 'status' => 'active', 'role_id' => null]);

        $this->actingAs($other)->get(app(PrivilegeClaimService::class)->linkFor($m))->assertRedirect();
        $this->assertNull($m->fresh()->user_id);
    }

    public function test_unsigned_activation_link_is_refused(): void
    {
        $m = $this->issue();
        $user = User::create([
            'name' => 'Rahim Uddin', 'email' => 'rahim@example.com', 'password' => Hash::make('password-123'),
            'status' => 'active', 'role_id' => null,
        ]);

        $this->actingAs($user)->get(route('account.privilege.claim', $m))->assertRedirect();
        $this->assertNull($m->fresh()->user_id);
    }

    /** Accounts that arrive already verified (Google sign-in, staff invite) skip the email step. */
    public function test_account_with_a_verified_email_gets_the_card_immediately(): void
    {
        $m = $this->issue();
        $user = User::factory()->create(['email' => 'rahim@example.com', 'status' => 'active', 'role_id' => null]);

        $this->assertNotNull($user->email_verified_at);
        $this->assertSame($user->id, $m->fresh()->user_id);
        $this->assertNull($m->fresh()->claim_sent_at);
    }
}
