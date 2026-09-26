<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\DeliveryCity;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    public static function numbers(): array
    {
        return [
            'local'            => ['01712345678', '+8801712345678'],
            'with country'     => ['8801712345678', '+8801712345678'],
            'e164'             => ['+8801712345678', '+8801712345678'],
            'spaced'           => ['+880 1712-345678', '+8801712345678'],
            'trunk zero kept'  => ['+880 01712345678', '+8801712345678'],
            'ten digits'       => ['0171234567', null],          // the bug: one digit short
            'twelve digits'    => ['017123456789', null],
            'bad operator'     => ['01212345678', null],
            'landline'         => ['0229876543', null],
            'letters'          => ['phone me', null],
            'empty'            => ['', null],
        ];
    }
    #[DataProvider('numbers')]
    public function test_normalisation(string $input, ?string $expected): void
    {
        $this->assertSame($expected, Phone::normalize($input));
    }

    public function test_checkout_rejects_an_incomplete_number_and_stores_one_format(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role_id' => null]);
        $category = Category::create(['name' => 'Bags', 'slug' => 'bags', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Tote', 'slug' => 'tote', 'sku' => 'T-1',
            'price' => 1000, 'stock' => 5, 'is_active' => true,
        ]);
        Cart::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);
        $city = DeliveryCity::create(['name' => 'Dhaka', 'is_active' => true, 'delivery_charge' => 60]);
        $area = DeliveryArea::create(['delivery_city_id' => $city->id, 'name' => 'Gulshan', 'postal_code' => '1212']);

        $payload = [
            'name' => 'Customer', 'email' => 'customer@example.com', 'address' => 'House 1',
            'delivery_area_id' => $area->id, 'payment_method' => 'cod',
        ];

        $this->actingAs($user)->post(route('checkout.store'), $payload + ['phone' => '0171234567'])
            ->assertSessionHasErrors('phone');
        $this->assertSame(0, Order::count());

        $this->actingAs($user)->post(route('checkout.store'), $payload + ['phone' => '017 1234-5678'])
            ->assertSessionHasNoErrors();
        $this->assertSame('+8801712345678', Order::first()->guest_phone);
    }

    public function test_profile_and_address_reject_bad_numbers(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role_id' => null]);

        $this->actingAs($user)->post(route('account.profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'phone' => '0171234567',
        ])->assertSessionHasErrors('phone');

        $this->actingAs($user)->post(route('account.addresses.store'), [
            'name' => 'Home', 'phone' => '12345', 'address_line' => 'House 1',
        ])->assertSessionHasErrors('phone');
    }
}
