<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // ── Dummy reviewer accounts (customers only, no admin role) ──────────
        $reviewers = [
            ['name' => 'Fatema Begum',   'email' => 'fatema@reviews.local'],
            ['name' => 'Rahim Ahmed',    'email' => 'rahim@reviews.local'],
            ['name' => 'Nusrat Jahan',   'email' => 'nusrat@reviews.local'],
            ['name' => 'Karim Hossain',  'email' => 'karim@reviews.local'],
            ['name' => 'Sumaiya Islam',  'email' => 'sumaiya@reviews.local'],
            ['name' => 'Mahfuz Rahman',  'email' => 'mahfuz@reviews.local'],
            ['name' => 'Tahmina Akter',  'email' => 'tahmina@reviews.local'],
            ['name' => 'Sabbir Khan',    'email' => 'sabbir@reviews.local'],
        ];

        $users = [];
        foreach ($reviewers as $r) {
            $users[] = User::firstOrCreate(
                ['email' => $r['email']],
                [
                    'name'               => $r['name'],
                    'password'           => Hash::make('dummy_password'),
                    'email_verified_at'  => now(),
                ]
            );
        }

        // ── Review data keyed by product slug ────────────────────────────────
        // Format: [user_index, rating, comment, days_ago]
        $reviewData = [
            'voyager-25l-backpack' => [
                [0, 5, 'Roomy and so comfortable on the shoulders. My new daily pack!', 4],
                [3, 5, 'Survived a rainy commute and everything stayed dry. Great build.', 11],
                [6, 4, 'Lots of pockets and the zippers feel solid. Highly recommend.', 19],
                [2, 5, 'Excellent value for the quality. Looks premium.', 24],
            ],
            'urban-daypack-18l' => [
                [1, 5, 'Sleek and lightweight — perfect for the office.', 3],
                [4, 4, 'Good organization inside. Straps are comfy.', 12],
                [7, 5, 'Exactly what I wanted for daily use.', 20],
            ],
            'trekker-35l-backpack' => [
                [2, 5, 'Took it on a weekend trip — fits everything and the rain cover is a bonus.', 6],
                [5, 4, 'Sturdy and spacious. Chest strap helps a lot.', 16],
            ],
            'metro-laptop-backpack-15' => [
                [1, 5, 'The padded laptop sleeve and USB port are game-changers.', 2],
                [4, 5, 'Carried my 15.6" laptop daily for months — still like new.', 9],
                [7, 4, 'Comfortable and professional looking. Anti-theft pocket is handy.', 15],
                [0, 5, 'Best laptop backpack I have owned at this price.', 23],
            ],
            'commuter-pro-17' => [
                [3, 5, 'Huge capacity and fits my 17" laptop easily.', 5],
                [6, 4, 'Great for travel days with the luggage strap.', 14],
            ],
            'junior-school-backpack' => [
                [2, 5, 'Lightweight and my son loves it. Reflective strips give peace of mind.', 3],
                [5, 5, 'Perfect size for primary school. Good quality.', 10],
                [0, 4, 'Comfortable straps and sturdy stitching.', 18],
            ],
            'roller-kids-trolley-bag' => [
                [4, 5, 'Wheels glide smoothly and it detaches into a backpack. Brilliant!', 4],
                [1, 5, 'Saved my daughter’s shoulders. Very well made.', 13],
                [6, 4, 'Sturdy handle and good capacity.', 21],
            ],
            'hydra-750ml-sports-bottle' => [
                [3, 5, 'Truly leak-proof and the one-hand lid is so convenient at the gym.', 2],
                [7, 4, 'Good size, no plastic taste. Happy with it.', 11],
                [2, 5, 'Great bottle for the price.', 20],
            ],
            'steel-1l-insulated-bottle' => [
                [0, 5, 'Kept my water cold all day in the heat. Love the finish.', 3],
                [5, 5, 'Solid stainless steel, no leaks, no sweating.', 12],
                [4, 4, 'Great quality and capacity.', 22],
            ],
            'thermo-500ml-flask' => [
                [1, 5, 'Coffee still hot after 8 hours. Fits perfectly in my bag.', 1],
                [6, 5, 'Excellent insulation and leak-proof. Buying another.', 8],
                [3, 4, 'Compact and well built.', 17],
            ],
            'summit-1l-travel-flask' => [
                [2, 5, 'Kept tea hot on an all-day trek. The cup lid is handy.', 6],
                [7, 4, 'Big capacity and rugged. Worth it.', 18],
            ],
            'packing-cubes-set-of-3' => [
                [0, 5, 'Turned my messy suitcase into perfect order. Great set!', 5],
                [4, 5, 'Lightweight, durable and the mesh tops are useful.', 15],
            ],
        ];

        // ── Insert reviews ────────────────────────────────────────────────────
        foreach ($reviewData as $slug => $entries) {
            $product = Product::where('slug', $slug)->first();
            if (! $product) continue;

            foreach ($entries as [$userIdx, $rating, $comment, $daysAgo]) {
                $user = $users[$userIdx];

                // Skip if already seeded (idempotent)
                if (Review::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
                    continue;
                }

                Review::create([
                    'product_id'  => $product->id,
                    'user_id'     => $user->id,
                    'rating'      => $rating,
                    'comment'     => $comment,
                    'is_approved' => true,
                    'created_at'  => now()->subDays($daysAgo),
                    'updated_at'  => now()->subDays($daysAgo),
                ]);
            }
        }
    }
}
