<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Order;
use App\Models\PrivilegeMembership;
use App\Models\Product;
use App\Models\User;
use App\Support\PrivilegeCardSettings;
use App\Support\PrivilegeDiscount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrivilegeDiscountTest extends TestCase
{
    use RefreshDatabase;

    private const CARD = '260615102100001';

    private function settings(array $overrides): void
    {
        app(PrivilegeCardSettings::class)->save($overrides);
    }

    private function member(string $status = 'approved'): User
    {
        $user = User::factory()->create(['status' => 'active', 'role_id' => null]);
        $user->privilegeMembership()->create([
            'status'      => $status,
            'card_number' => $status === 'pending' ? null : self::CARD,
            'applied_at'  => now(),
        ]);
        return $user;
    }

    /** One 1000৳ item in the cart of the current session / user. */
    private function cartFor(?User $user): DeliveryArea
    {
        $category = Category::create(['name' => 'Bags', 'slug' => 'bags', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Tote', 'slug' => 'tote', 'sku' => 'T-1',
            'price' => 1000, 'stock' => 10, 'is_active' => true,
        ]);
        // Pin the guest's session id so the cart survives across test requests.
        $sessionId = Str::random(40);
        $this->withCredentials()->withCookie(config('session.cookie'), $sessionId);
        Cart::create([
            'user_id'    => $user?->id,
            'session_id' => $user ? null : $sessionId,
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);
        $city = DeliveryCity::create(['name' => 'Dhaka', 'is_active' => true, 'delivery_charge' => 60]);
        return DeliveryArea::create(['delivery_city_id' => $city->id, 'name' => 'Gulshan', 'postal_code' => '1212']);
    }

    private function orderPayload(DeliveryArea $area, array $extra = []): array
    {
        return array_merge([
            'name' => 'Guest', 'email' => 'guest@example.com', 'phone' => '01700000000',
            'address' => 'House 1', 'delivery_area_id' => $area->id, 'payment_method' => 'cod',
        ], $extra);
    }

    public function test_discount_is_off_by_default(): void
    {
        $user = $this->member();
        $this->assertNull(app(PrivilegeDiscount::class)->membershipFor($user, null));
    }

    public function test_bigger_of_coupon_and_member_discount_applies_unless_stacking(): void
    {
        $this->settings(['member_discount_percent' => 10]);
        $d = app(PrivilegeDiscount::class);

        $this->assertSame(100.0, $d->combine(1000, 50, true)['total']);   // member wins
        $this->assertFalse($d->combine(1000, 50, true)['coupon_applied']);
        $this->assertSame(0.0, $d->combine(1000, 150, true)['member']);   // coupon wins

        $this->settings(['discount_stacks_with_coupon' => true]);
        $this->assertSame(150.0, $d->combine(1000, 50, true)['total']);
    }

    public function test_only_approved_members_qualify(): void
    {
        $this->settings(['member_discount_percent' => 10]);
        $this->assertNull(app(PrivilegeDiscount::class)->membershipFor($this->member('revoked'), null));
    }

    public function test_member_price_is_shared_only_with_approved_members(): void
    {
        $this->settings(['member_discount_percent' => 10]);

        $this->actingAs($this->member())->get(route('account.privilege'))
            ->assertInertia(fn ($page) => $page->where('auth.user.member_discount', 10));

        $this->actingAs($this->member('pending'))->get(route('account.privilege'))
            ->assertInertia(fn ($page) => $page->where('auth.user.member_discount', 0));
    }

    public function test_logged_in_member_gets_discount_on_order(): void
    {
        $this->settings(['member_discount_percent' => 10]);
        $user = $this->member();
        $area = $this->cartFor($user);

        $this->actingAs($user)->post(route('checkout.store'), $this->orderPayload($area))->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertEquals(100, $order->member_discount);
        $this->assertEquals(100, $order->discount);
        $this->assertSame(self::CARD, $order->privilege_card_number);
        $this->assertEquals(1000 + $order->delivery_charge - 100, $order->total);
    }

    public function test_guest_card_number_gets_discount(): void
    {
        $this->settings(['member_discount_percent' => 10]);
        $this->member();
        $area = $this->cartFor(null);

        $this->postJson(route('checkout.privilege-card'), ['card_number' => '2606 1510 2100001'])
            ->assertOk()->assertJson(['valid' => true, 'member_discount' => 100]);

        $this->post(route('checkout.store'), $this->orderPayload($area, ['privilege_card' => '2606 1510 2100001']))
            ->assertSessionHasNoErrors();
        $this->assertEquals(100, Order::first()->member_discount);
    }

    public function test_guest_invalid_card_is_rejected(): void
    {
        $this->settings(['member_discount_percent' => 10]);
        $area = $this->cartFor(null);

        $this->postJson(route('checkout.privilege-card'), ['card_number' => '999'])->assertStatus(422);
        $this->post(route('checkout.store'), $this->orderPayload($area, ['privilege_card' => '999']))
            ->assertSessionHasErrors('privilege_card');
        $this->assertSame(0, Order::count());
    }

    public function test_guest_field_can_be_disabled(): void
    {
        $this->settings(['member_discount_percent' => 10, 'checkout_card_field' => false]);
        $this->member();
        $area = $this->cartFor(null);

        $this->postJson(route('checkout.privilege-card'), ['card_number' => self::CARD])->assertStatus(422);
        $this->post(route('checkout.store'), $this->orderPayload($area, ['privilege_card' => self::CARD]))
            ->assertSessionHasNoErrors();
        $this->assertEquals(0, Order::first()->member_discount);
    }
}
