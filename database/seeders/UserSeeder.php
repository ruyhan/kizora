<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Role assignment is handled by RbacSeeder (admin email → Super Admin,
        // everyone else → Consumer), so we don't set roles here.
        User::create([
            'name' => 'Admin',
            'email' => 'admin@kizora.com.bd',
            'phone' => '+8801730728954',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Test Customer',
            'email' => 'customer@kizora.com.bd',
            'phone' => '+8801700000000',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
