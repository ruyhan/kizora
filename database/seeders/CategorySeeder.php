<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Parent (mother) categories ───────────────────────────────────────
        $bags = Category::create([
            'name' => 'Bags', 'slug' => 'bags',
            'description' => 'Backpacks, school bags and trolleys built to carry your world.',
            'icon' => 'bag', 'sort_order' => 1,
        ]);

        $drinkware = Category::create([
            'name' => 'Drinkware', 'slug' => 'drinkware',
            'description' => 'Bottles and flasks that keep every sip just right.',
            'icon' => 'bottle', 'sort_order' => 2,
        ]);

        // ── Sub-categories under Bags ────────────────────────────────────────
        $children = [
            ['parent_id' => $bags->id, 'name' => 'Backpacks', 'slug' => 'backpacks',
                'description' => 'Everyday backpacks built for comfort, capacity and style.', 'icon' => 'bag', 'sort_order' => 1],
            ['parent_id' => $bags->id, 'name' => 'Laptop Backpacks', 'slug' => 'laptop-backpacks',
                'description' => 'Padded, water-resistant packs with dedicated laptop sleeves and USB ports.', 'icon' => 'laptop', 'sort_order' => 2],
            ['parent_id' => $bags->id, 'name' => 'School Bags', 'slug' => 'school-bags',
                'description' => 'Lightweight, sturdy and fun school bags kids love to carry.', 'icon' => 'school', 'sort_order' => 3],
            ['parent_id' => $bags->id, 'name' => "Kids' Trolley Bags", 'slug' => 'kids-trolley-bags',
                'description' => 'Wheeled school bags with retractable handles to save little shoulders.', 'icon' => 'trolley', 'sort_order' => 4],

            // ── Sub-categories under Drinkware ──
            ['parent_id' => $drinkware->id, 'name' => 'Water Bottles', 'slug' => 'water-bottles',
                'description' => 'Leak-proof bottles for gym, office, school and travel.', 'icon' => 'bottle', 'sort_order' => 1],
            ['parent_id' => $drinkware->id, 'name' => 'Thermos & Flasks', 'slug' => 'thermos-flasks',
                'description' => 'Double-wall vacuum flasks that keep drinks hot or cold for hours.', 'icon' => 'flask', 'sort_order' => 2],

            // ── Top-level leaf category ──
            ['parent_id' => null, 'name' => 'Accessories', 'slug' => 'accessories',
                'description' => 'Pouches, organizers and add-ons to complete your everyday carry.', 'icon' => 'tag', 'sort_order' => 3],
        ];

        foreach ($children as $category) {
            Category::create($category);
        }
    }
}
