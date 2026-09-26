<?php

namespace Tests\Feature;

use App\Models\PrivilegeMembership;
use App\Models\Role;
use App\Models\User;
use App\Support\PrivilegeCardSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivilegeMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function consumer(string $name = 'Othiya Israq'): User
    {
        return User::factory()->create(['name' => $name, 'status' => 'active', 'role_id' => null]);
    }

    /** Applications need one completed order worth at least the configured minimum. */
    private function qualify(User $user, float $total = 5000): void
    {
        \App\Models\Order::create([
            'user_id' => $user->id, 'guest_name' => $user->name, 'guest_email' => $user->email,
            'guest_phone' => '+8801712345678', 'delivery_address' => 'House 1', 'status' => 'completed',
            'subtotal' => $total, 'delivery_charge' => 0, 'discount' => 0, 'total' => $total, 'payment_method' => 'cod',
        ]);
    }

    private function superAdmin(): User
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_staff' => true]);
        return User::factory()->create(['status' => 'active', 'role_id' => $role->id]);
    }

    public function test_customer_applies_and_request_is_pending(): void
    {
        $user = $this->consumer();
        $this->qualify($user);

        $this->actingAs($user)->get(route('account.privilege'))->assertOk();
        $this->actingAs($user)->post(route('account.privilege.apply'), ['phone' => '01712345678', 'agree' => true])
            ->assertSessionHasNoErrors();

        $m = $user->fresh()->privilegeMembership;
        $this->assertSame('pending', $m->status);
        $this->assertNull($m->card_number);
        $this->assertSame($user->email, $m->email);
        $this->assertSame('+8801712345678', $m->phone);      // stored in one format
    }

    public function test_customer_cannot_apply_before_the_minimum_order(): void
    {
        $user = $this->consumer();
        $this->qualify($user, 4999);                          // just under the ৳5,000 requirement

        $this->actingAs($user)->get(route('account.privilege'))
            ->assertInertia(fn ($page) => $page->where('eligibility.qualified', false)->where('eligibility.required', 5000));

        $this->actingAs($user)->post(route('account.privilege.apply'), ['phone' => '01712345678', 'agree' => true]);
        $this->assertNull($user->fresh()->privilegeMembership);
    }

    public function test_only_completed_orders_count_towards_the_requirement(): void
    {
        $user = $this->consumer();
        \App\Models\Order::create([
            'user_id' => $user->id, 'guest_name' => $user->name, 'guest_phone' => '+8801712345678',
            'delivery_address' => 'House 1', 'status' => 'pending', 'subtotal' => 9000,
            'delivery_charge' => 0, 'discount' => 0, 'total' => 9000, 'payment_method' => 'cod',
        ]);

        $this->actingAs($user)->post(route('account.privilege.apply'), ['phone' => '01712345678', 'agree' => true]);
        $this->assertNull($user->fresh()->privilegeMembership);
    }

    public function test_terms_must_be_accepted(): void
    {
        $user = $this->consumer();
        $this->qualify($user);

        $this->actingAs($user)->post(route('account.privilege.apply'), ['phone' => '01712345678'])
            ->assertSessionHasErrors('agree');
    }

    public function test_application_rejects_a_short_phone_number(): void
    {
        $user = $this->consumer();
        $this->qualify($user);

        $this->actingAs($user)->post(route('account.privilege.apply'), ['phone' => '0171234567', 'agree' => true])
            ->assertSessionHasErrors('phone');
    }

    public function test_approval_issues_random_card_numbers_by_default(): void
    {
        $admin = $this->superAdmin();
        $ma = $this->consumer('First Member')->privilegeMembership()->create(['status' => 'pending', 'applied_at' => now()]);
        $mb = $this->consumer('Second Member')->privilegeMembership()->create(['status' => 'pending', 'applied_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.privilege.approve', $ma))->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.privilege.approve', $mb))->assertRedirect();

        foreach ([$ma->fresh(), $mb->fresh()] as $m) {
            $this->assertMatchesRegularExpression('/^26061510\d{7}$/', $m->card_number);
            $this->assertNull($m->serial);
        }
        $this->assertNotSame($ma->fresh()->card_number, $mb->fresh()->card_number);
    }

    public function test_approval_issues_sequential_card_numbers(): void
    {
        app(PrivilegeCardSettings::class)->save(['number_mode' => 'sequential']);
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
