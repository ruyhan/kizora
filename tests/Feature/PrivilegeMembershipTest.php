<?php

namespace Tests\Feature;

use App\Models\PrivilegeMembership;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivilegeMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function consumer(string $name = 'Othiya Israq'): User
    {
        return User::factory()->create(['name' => $name, 'status' => 'active', 'role_id' => null]);
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_staff' => true]);
        return User::factory()->create(['status' => 'active', 'role_id' => $role->id]);
    }

    public function test_customer_applies_and_request_is_pending(): void
    {
        $user = $this->consumer();

        $this->actingAs($user)->get(route('account.privilege'))->assertOk();
        $this->actingAs($user)->post(route('account.privilege.apply'), ['phone' => '01700000000', 'agree' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame('pending', $user->fresh()->privilegeMembership->status);
        $this->assertNull($user->fresh()->privilegeMembership->card_number);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->actingAs($this->consumer())->post(route('account.privilege.apply'), ['phone' => '01700000000'])
            ->assertSessionHasErrors('agree');
    }

    public function test_approval_issues_sequential_card_numbers(): void
    {
        $admin = $this->superAdmin();
        $a = $this->consumer('First Member');
        $b = $this->consumer('Second Member');
        $ma = $a->privilegeMembership()->create(['status' => 'pending', 'applied_at' => now()]);
        $mb = $b->privilegeMembership()->create(['status' => 'pending', 'applied_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.privilege.approve', $ma))->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.privilege.approve', $mb))->assertRedirect();

        $this->assertSame('260615102100001', $ma->fresh()->card_number);
        $this->assertSame('260615102100002', $mb->fresh()->card_number);
        $this->assertSame('2606 1510 2100001', PrivilegeMembership::formatNumber($ma->fresh()->card_number));
        $this->assertSame('approved', $ma->fresh()->status);
    }

    public function test_revoked_member_keeps_card_number_on_reapproval(): void
    {
        $admin = $this->superAdmin();
        $m = $this->consumer()->privilegeMembership()->create(['status' => 'pending', 'applied_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.privilege.approve', $m));
        $number = $m->fresh()->card_number;
        $this->actingAs($admin)->patch(route('admin.privilege.revoke', $m), ['reason' => 'Misuse']);
        $this->assertSame('revoked', $m->fresh()->status);

        $this->actingAs($admin)->patch(route('admin.privilege.approve', $m));
        $this->assertSame($number, $m->fresh()->card_number);
    }

    public function test_consumer_cannot_access_admin_routes(): void
    {
        $m = $this->consumer()->privilegeMembership()->create(['status' => 'pending', 'applied_at' => now()]);

        $this->actingAs($this->consumer('Other'))->patch(route('admin.privilege.approve', $m));
        $this->assertSame('pending', $m->fresh()->status);
    }
}
