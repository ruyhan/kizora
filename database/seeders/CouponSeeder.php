<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::insert([
            [
                'code' => 'LAVENDER10',
                'type' => 'percentage',
                'value' => 10,
                'minimum_order' => 200,
                'usage_limit' => 100,
                'used_count' => 0,
                'expires_at' => '2025-12-31',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'FIRSTORDER',
                'type' => 'fixed',
                'value' => 50,
                'minimum_order' => 300,
                'usage_limit' => 50,
                'used_count' => 0,
                'expires_at' => '2025-12-31',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
