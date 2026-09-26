<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Support\PointSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardPointsTest extends TestCase
{
    use RefreshDatabase;

    private function customer(int $points = 0): User
    {
        $user = User::factory()->create(['status' => 'active', 'role_id' => null]);
        $user->forceFill(['reward_points' => $points])->save();
        return $user;
    }

    private function order(User $user, array $attrs = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $user->id, 'guest_name' => $user->name, 'guest_email' => $user->email, 'guest_phone' => '01700000000',
            'delivery_address' => 'House 1', 'status' => 'pending', 'subtotal' => 1250, 'delivery_charge' => 60,
            'discount' => 0, 'total' => 1310, 'payment_method' => 'cod',
        ], $attrs));
    }

    /** One 1000৳ item in the user's cart + a delivery area. */
    private function cartFor(User $user): DeliveryArea
    {
        $category = Category::create(['name' => 'Bags', 'slug' => 'bags', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Tote', 'slug' => 'tote', 'sku' => 'T-1',
            'price' => 1000, 'stock' => 10, 'is_active' => true,
        ]);
        Cart::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $city = DeliveryCity::create(['name' => 'Dhaka', 'is_active' => true, 'delivery_charge' => 60]);
        return DeliveryArea::create(['delivery_city_id' => $city->id, 'name' => 'Gulshan', 'postal_code' => '1212']);
    }

    private function checkout(User $user, DeliveryArea $area, int $redeem)
    {
        return $this->actingAs($user)->post(route('checkout.store'), [
            'name' => $user->name, 'email' => $user->email, 'phone' => '01700000000', 'address' => 'House 1',
            'delivery_area_id' => $area->id, 'payment_method' => 'cod', 'redeem_points' => $redeem,
        ]);
    }

    public function test_completed_order_earns_points_once(): void
    {
        $user = $this->customer();
        $order = $this->order($user);

        $order->update(['status' => 'completed']);
        $this->assertSame(12, $user->fresh()->reward_points);       // ৳1250 → 12 points
        $this->assertSame(12, $order->fresh()->points_earned);

        $order->update(['status' => 'processing']);
        $order->update(['status' => 'completed']);
        $this->assertSame(12, $user->fresh()->reward_points);       // not awarded twice
    }

    public function test_points_follow_admin_rules(): void
    {
        app(PointSettings::class)->save(['earn_amount' => 50, 'earn_points' => 2]);
        $user = $this->customer();
        $this->order($user)->update(['status' => 'completed']);

        $this->assertSame(50, $user->fresh()->reward_points);       // floor(1250 / 50) × 2
    }

    public function test_privilege_members_do_not_earn_points(): void
    {
        $user = $this->customer();
        $user->privilegeMembership()->create(['status' => 'approved', 'card_number' => '260615101234567', 'applied_at' => now()]);
        $this->order($user)->update(['status' => 'completed']);

        $this->assertSame(0, $user->fresh()->reward_points);
    }

    public function test_cancelling_a_completed_order_takes_points_back(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $order->update(['status' => 'completed']);
        $order->update(['status' => 'cancelled']);

        $this->assertSame(0, $user->fresh()->reward_points);
    }

    public function test_checkout_redeems_points(): void
    {
        $user = $this->customer(60);
        $area = $this->cartFor($user);

        $this->checkout($user, $area, 50)->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertSame(50, $order->points_redeemed);
        $this->assertEquals(50, $order->points_discount);
        $this->assertEquals(50, $order->discount);
        $this->assertEquals(1000 + $order->delivery_charge - 50, $order->total);
        $this->assertSame(10, $user->fresh()->reward_points);
    }

    public function test_cancelled_order_returns_redeemed_points(): void
    {
        $user = $this->customer(60);
        $this->checkout($user, $this->cartFor($user), 60)->assertSessionHasNoErrors();
        $this->assertSame(0, $user->fresh()->reward_points);

        Order::first()->update(['status' => 'cancelled']);
        $this->assertSame(60, $user->fresh()->reward_points);
    }

    public function test_minimum_points_is_enforced(): void
    {
        $user = $this->customer(40);
        $this->checkout($user, $this->cartFor($user), 40)->assertSessionHasErrors('redeem_points');

        $this->assertSame(0, Order::count());
        $this->assertSame(40, $user->fresh()->reward_points);
    }

    public function test_cannot_redeem_more_than_balance(): void
    {
        $user = $this->customer(60);
        $this->checkout($user, $this->cartFor($user), 80)->assertSessionHasErrors('redeem_points');
        $this->assertSame(60, $user->fresh()->reward_points);
    }

    public function test_badge_is_hidden_for_privilege_members(): void
    {
        $user = $this->customer(25);
        $this->actingAs($user)->get(route('account.privilege'))
            ->assertInertia(fn ($page) => $page->where('auth.user.reward_points', 25));

        $user->privilegeMembership()->create(['status' => 'approved', 'card_number' => '260615101234567', 'applied_at' => now()]);
        $this->actingAs($user)->get(route('account.privilege'))
            ->assertInertia(fn ($page) => $page->where('auth.user.reward_points', null));
    }

    public function test_admin_can_adjust_points_and_change_rules(): void
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_staff' => true]);
        $admin = User::factory()->create(['status' => 'active', 'role_id' => $role->id]);
        $user = $this->customer(10);

        $this->actingAs($admin)->get(route('admin.privilege.points'))->assertOk();
        $this->actingAs($admin)->post(route('admin.privilege.points.adjust', $user), ['points' => 40, 'note' => 'Goodwill'])
            ->assertSessionHasNoErrors();
        $this->assertSame(50, $user->fresh()->reward_points);

        $this->actingAs($admin)->post(route('admin.privilege.points.update'), [
            'enabled' => true, 'earn_amount' => 200, 'earn_points' => 1, 'point_value' => 0.5, 'min_redeem' => 100, 'max_redeem_percent' => 50,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(200, app(PointSettings::class)->get()['earn_amount']);
    }

    public function test_customers_cannot_reach_point_management(): void
    {
        $user = $this->customer(10);
        $this->actingAs($user)->post(route('admin.privilege.points.adjust', $user), ['points' => 1000]);
        $this->assertSame(10, $user->fresh()->reward_points);
    }
}
