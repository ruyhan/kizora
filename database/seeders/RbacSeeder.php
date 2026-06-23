<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = config('permissions');

        // 1) Sync permission catalogue ------------------------------------
        $allSlugs = [];
        foreach ($catalog as $module => $perms) {
            foreach ($perms as $slug => $label) {
                Permission::updateOrCreate(['slug' => $slug], ['name' => $label, 'module' => $module]);
                $allSlugs[] = $slug;
            }
        }

        // 2) System roles -------------------------------------------------
        $roles = [
            'super_admin' => ['name' => 'Super Admin', 'desc' => 'Full unrestricted access to everything.', 'staff' => true],
            'admin'       => ['name' => 'Admin',       'desc' => 'Full administrative access.',            'staff' => true],
            'manager'     => ['name' => 'Manager',     'desc' => 'Manages catalog, orders and customers.', 'staff' => true],
            'staff'       => ['name' => 'Staff',       'desc' => 'Day-to-day operational access.',          'staff' => true],
            'consumer'    => ['name' => 'Consumer',    'desc' => 'Storefront customer. No admin access.',   'staff' => false],
        ];
        $roleModels = [];
        foreach ($roles as $slug => $r) {
            $roleModels[$slug] = Role::updateOrCreate(['slug' => $slug], [
                'name' => $r['name'], 'description' => $r['desc'], 'is_system' => true, 'is_staff' => $r['staff'],
            ]);
        }

        // 3) Role → permission assignments --------------------------------
        $pick = fn (array $slugs) => Permission::whereIn('slug', $slugs)->pluck('id')->all();

        $manager = $this->slugsFor($catalog, [
            'Dashboard' => '*', 'Products' => '*', 'Categories' => '*', 'Orders' => '*',
            'Customers' => ['customers.view', 'customers.edit', 'customers.view_orders'],
            'Settings' => ['settings.view'], 'Reports' => '*', 'CMS' => '*', 'Media' => '*', 'Marketing' => '*',
            'Inventory' => '*', 'POS' => '*', 'Invoice' => '*', 'Invoice Settings' => ['invoice_settings.view'],
            'Chatbot' => ['chatbot.settings.view', 'chatbot.orders.view', 'chatbot.orders.edit', 'chatbot.analytics.view', 'chatbot.analytics.export'],
        ]);
        $staff = [
            'dashboard.view', 'products.view', 'products.edit', 'categories.view',
            'orders.view', 'orders.update_status', 'customers.view', 'customers.view_orders',
            'media.view', 'media.upload',
            'inventory.view', 'inventory.update',
            'pos.view', 'pos.create', 'pos.orders',
            'invoice.view', 'invoice.download', 'invoice.print',
            'chatbot.orders.view',
        ];

        $roleModels['super_admin']->permissions()->sync($pick($allSlugs)); // explicit + bypass
        $roleModels['admin']->permissions()->sync($pick($allSlugs));
        $roleModels['manager']->permissions()->sync($pick($manager));
        $roleModels['staff']->permissions()->sync($pick($staff));
        $roleModels['consumer']->permissions()->sync([]);

        // 4) Migrate existing users into roles ----------------------------
        // Known admin emails become Super Admin; everyone else is a Consumer.
        $adminEmails = ['admin@kizora.com.bd'];
        User::query()->whereNull('role_id')->get()->each(function (User $u) use ($roleModels, $adminEmails) {
            $slug = in_array($u->email, $adminEmails, true) ? 'super_admin' : 'consumer';
            $u->forceFill(['role_id' => $roleModels[$slug]->id, 'status' => $u->status ?? 'active'])->saveQuietly();
        });

        // 5) Dummy staff + consumers + orders (idempotent) ----------------
        if (! User::where('email', 'manager@kizora.com.bd')->exists()) {
            $this->seedStaff($roleModels);
            $this->seedConsumers($roleModels['consumer']);
            $this->seedOrders();
        }
    }

    /** Resolve a module-map (['Module' => '*' | [slugs]]) into a flat slug list. */
    private function slugsFor(array $catalog, array $map): array
    {
        $out = [];
        foreach ($map as $module => $sel) {
            $modSlugs = array_keys($catalog[$module] ?? []);
            $out = array_merge($out, $sel === '*' ? $modSlugs : array_intersect($modSlugs, $sel));
        }
        return array_values($out);
    }

    private function seedStaff(array $roles): void
    {
        $staff = [
            ['Naveen Admin',   'admin2@kizora.com.bd',   'admin'],
            ['Priya Manager',  'manager@kizora.com.bd',  'manager'],
            ['Rahim Manager',  'manager2@kizora.com.bd', 'manager'],
            ['Sadia Staff',    'staff@kizora.com.bd',    'staff'],
            ['Imran Staff',    'staff2@kizora.com.bd',   'staff'],
        ];
        foreach ($staff as [$name, $email, $slug]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => '01' . fake()->numerify('#########'),
                'password' => Hash::make('password'),
                'role_id' => $roles[$slug]->id,
                'status' => 'active',
                'email_verified_at' => now(),
                'last_login_at' => now()->subDays(rand(0, 10)),
            ]);
        }
    }

    private function seedConsumers(Role $consumer): void
    {
        $statuses = array_merge(array_fill(0, 44, 'active'), array_fill(0, 4, 'inactive'), array_fill(0, 2, 'suspended'));
        for ($i = 1; $i <= 50; $i++) {
            $created = now()->subDays(rand(5, 540));
            User::create([
                'name' => fake()->name(),
                'email' => "consumer{$i}@demo.kizora.test",
                'phone' => '01' . fake()->numerify('#########'),
                'password' => Hash::make('password'),
                'role_id' => $consumer->id,
                'status' => $statuses[$i % count($statuses)],
                'email_verified_at' => rand(0, 4) ? $created : null,
                'created_at' => $created,
                'updated_at' => $created,
                'last_login_at' => rand(0, 1) ? now()->subDays(rand(0, 30)) : null,
            ]);
        }
    }

    private function seedOrders(): void
    {
        $products = Product::all();
        if ($products->isEmpty()) {
            return;
        }

        $consumers = User::where('email', 'like', '%@demo.kizora.test')->get();
        $statuses = ['pending', 'confirmed', 'processing', 'out_for_delivery', 'completed', 'cancelled'];
        $payStatuses = ['pending', 'paid', 'failed'];
        $methods = ['cod', 'bkash', 'nagad', 'card'];
        $seq = 1000;

        foreach ($consumers as $c) {
            if (rand(0, 100) < 35) {
                continue; // ~35% of customers have no orders
            }
            foreach (range(1, rand(1, 4)) as $n) {
                $when = $c->created_at->copy()->addDays(rand(1, 120));
                if ($when->isFuture()) {
                    $when = now()->subDays(rand(0, 60));
                }
                $items = $products->random(rand(1, 3));
                $subtotal = 0;
                $order = Order::create([
                    'order_number' => 'KZ' . str_pad(++$seq, 6, '0', STR_PAD_LEFT),
                    'user_id' => $c->id,
                    'guest_name' => null,
                    'delivery_address' => fake()->streetAddress(),
                    'delivery_city' => 'Dhaka',
                    'delivery_area' => fake()->randomElement(['Banani', 'Dhanmondi', 'Gulshan', 'Mirpur', 'Uttara']),
                    'delivery_postal_code' => (string) rand(1200, 1230),
                    'status' => fake()->randomElement($statuses),
                    'subtotal' => 0,
                    'delivery_charge' => 60,
                    'discount' => 0,
                    'total' => 0,
                    'payment_method' => fake()->randomElement($methods),
                    'payment_status' => fake()->randomElement($payStatuses),
                    'created_at' => $when,
                    'updated_at' => $when,
                ]);
                foreach ($items as $p) {
                    $qty = rand(1, 3);
                    $price = (float) ($p->discount_price ?: $p->price);
                    $subtotal += $price * $qty;
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $p->id,
                        'product_name' => $p->name,
                        'product_image' => $p->featured_image_url,
                        'price' => $price,
                        'quantity' => $qty,
                        'total' => $price * $qty,
                    ]);
                }
                $order->forceFill(['subtotal' => $subtotal, 'total' => $subtotal + 60])->save();
            }
        }
    }
}
