<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $cat = fn (string $slug) => Category::where('slug', $slug)->first()->id;

        $backpacks = $cat('backpacks');
        $laptop    = $cat('laptop-backpacks');
        $school    = $cat('school-bags');
        $trolley   = $cat('kids-trolley-bags');
        $bottles   = $cat('water-bottles');
        $flasks    = $cat('thermos-flasks');
        $access    = $cat('accessories');

        $products = [
            // ── Backpacks ──
            [
                'category_id' => $backpacks, 'name' => 'Kizora Voyager 25L Backpack', 'slug' => 'voyager-25l-backpack',
                'sku' => 'KZ-BP-001',
                'short_description' => 'A do-it-all 25L daypack with a padded back and water-resistant build.',
                'description' => 'The Voyager is our flagship everyday backpack. A roomy 25L main compartment, padded back panel and ergonomic straps make it ideal for commuting, college and short trips — while the water-resistant fabric keeps your gear safe in any weather.',
                'ingredients' => 'Water-resistant polyester, Padded back panel, YKK-style zippers, Ergonomic straps, Side bottle pockets',
                'price' => 2490, 'discount_price' => 1990, 'weight' => '25 L · 30×46×16 cm', 'stock' => 60,
                'is_featured' => true, 'show_on_hero' => true, 'tags' => ['backpack', 'daypack', 'travel', 'bestseller'],
            ],
            [
                'category_id' => $backpacks, 'name' => 'Kizora Urban Daypack 18L', 'slug' => 'urban-daypack-18l',
                'sku' => 'KZ-BP-002',
                'short_description' => 'A sleek, lightweight 18L pack for the daily commute.',
                'description' => 'Minimal on the outside, organized on the inside. The Urban Daypack keeps essentials tidy with a front organizer and padded straps for all-day comfort.',
                'ingredients' => '600D polyester, Padded shoulder straps, Front organizer, Quick-access top pocket',
                'price' => 1690, 'discount_price' => null, 'weight' => '18 L', 'stock' => 75,
                'is_featured' => true, 'tags' => ['backpack', 'commute', 'lightweight'],
            ],
            [
                'category_id' => $backpacks, 'name' => 'Kizora Trekker 35L Backpack', 'slug' => 'trekker-35l-backpack',
                'sku' => 'KZ-BP-003',
                'short_description' => 'A 35L adventure pack with chest strap and rain cover.',
                'description' => 'Built for weekends away. The Trekker offers 35L of ripstop-nylon capacity, a stabilising chest strap and an included rain cover so you can pack and go.',
                'ingredients' => 'Ripstop nylon, Chest strap, Rain cover included, Compression straps',
                'price' => 3290, 'discount_price' => 2890, 'weight' => '35 L', 'stock' => 35,
                'is_featured' => false, 'tags' => ['backpack', 'travel', 'hiking'],
            ],

            // ── Laptop Backpacks ──
            [
                'category_id' => $laptop, 'name' => 'Kizora Metro Laptop Backpack 15.6"', 'slug' => 'metro-laptop-backpack-15',
                'sku' => 'KZ-LB-001',
                'short_description' => 'Water-resistant 22L pack with a USB port and padded 15.6" sleeve.',
                'description' => 'The Metro is made for work and study. A dedicated padded 15.6" laptop sleeve, built-in USB charging port and a hidden anti-theft pocket keep your tech safe and your day moving.',
                'ingredients' => 'Water-resistant fabric, Padded 15.6" sleeve, USB charging port, Anti-theft back pocket, Luggage strap',
                'price' => 2890, 'discount_price' => 2490, 'weight' => '22 L · fits 15.6"', 'stock' => 70,
                'is_featured' => true, 'show_on_hero' => true, 'tags' => ['laptop', 'backpack', 'office', 'usb', 'bestseller'],
            ],
            [
                'category_id' => $laptop, 'name' => 'Kizora Executive Slim 14"', 'slug' => 'executive-slim-14',
                'sku' => 'KZ-LB-002',
                'short_description' => 'A slim, professional pack that slides over your luggage handle.',
                'description' => 'Clean lines and a slim profile make the Executive perfect for the office. Ballistic nylon resists scuffs while the trolley strap makes travel effortless.',
                'ingredients' => 'Ballistic nylon, Padded 14" sleeve, Trolley pass-through strap, Organizer panel',
                'price' => 2690, 'discount_price' => null, 'weight' => '16 L · fits 14"', 'stock' => 45,
                'is_featured' => false, 'tags' => ['laptop', 'backpack', 'office', 'slim'],
            ],
            [
                'category_id' => $laptop, 'name' => 'Kizora Commuter Pro 17"', 'slug' => 'commuter-pro-17',
                'sku' => 'KZ-LB-003',
                'short_description' => 'Big-capacity 24L pack for 17" laptops and long days.',
                'description' => 'When you carry more, the Commuter Pro delivers — padded sleeves for a 17" laptop and tablet, a USB port and a luggage strap for travel days.',
                'ingredients' => 'Water-resistant fabric, USB port, Luggage strap, Padded laptop & tablet sleeves',
                'price' => 3490, 'discount_price' => 2990, 'weight' => '24 L · fits 17"', 'stock' => 40,
                'is_featured' => true, 'tags' => ['laptop', 'backpack', 'travel', 'usb'],
            ],

            // ── School Bags ──
            [
                'category_id' => $school, 'name' => 'Kizora Junior School Backpack', 'slug' => 'junior-school-backpack',
                'sku' => 'KZ-SB-001',
                'short_description' => 'Lightweight, comfy school backpack with reflective safety strips.',
                'description' => 'Designed for little explorers. Padded straps, a lightweight build and reflective strips for safer mornings — plus a handy name tag inside.',
                'ingredients' => 'Lightweight polyester, Padded straps, Reflective strips, Inner name tag',
                'price' => 1490, 'discount_price' => null, 'weight' => '18 L', 'stock' => 90,
                'is_featured' => true, 'tags' => ['school', 'kids', 'lightweight'],
            ],
            [
                'category_id' => $school, 'name' => 'Kizora Campus 20L School Bag', 'slug' => 'campus-20l-school-bag',
                'sku' => 'KZ-SB-002',
                'short_description' => 'Roomy 20L bag with compartments for books, tablet and bottle.',
                'description' => 'From primary to high school, the Campus keeps everything organized with multiple compartments and a dedicated side bottle pocket.',
                'ingredients' => 'Durable polyester, Multiple compartments, Side bottle pocket, Padded back',
                'price' => 1790, 'discount_price' => 1490, 'weight' => '20 L', 'stock' => 65,
                'is_featured' => false, 'tags' => ['school', 'students'],
            ],

            // ── Kids' Trolley Bags ──
            [
                'category_id' => $trolley, 'name' => 'Kizora Roller Kids Trolley Bag', 'slug' => 'roller-kids-trolley-bag',
                'sku' => 'KZ-TB-001',
                'short_description' => 'Detachable trolley bag that saves little shoulders.',
                'description' => 'Roll it or wear it. The Roller features a retractable handle and sturdy wheels, and the bag detaches to become a regular padded backpack when needed.',
                'ingredients' => 'Sturdy wheels, Retractable handle, Detachable padded straps, Water-resistant',
                'price' => 3290, 'discount_price' => 2790, 'weight' => 'Detachable trolley · 19 L', 'stock' => 30,
                'is_featured' => true, 'tags' => ['trolley', 'kids', 'school', 'wheels'],
            ],
            [
                'category_id' => $trolley, 'name' => 'Kizora Glide 6-Wheel Trolley', 'slug' => 'glide-6-wheel-trolley',
                'sku' => 'KZ-TB-002',
                'short_description' => 'Six silent wheels and an aluminium handle for a smooth ride.',
                'description' => 'The Glide rolls smoothly over any surface on six quiet wheels, with a height-adjustable aluminium handle and a reinforced base built to last the school year.',
                'ingredients' => '6 silent wheels, Aluminium handle, Reinforced base, Multiple compartments',
                'price' => 2990, 'discount_price' => null, 'weight' => '20 L · 6-wheel', 'stock' => 28,
                'is_featured' => false, 'tags' => ['trolley', 'kids', 'school'],
            ],

            // ── Water Bottles ──
            [
                'category_id' => $bottles, 'name' => 'Kizora Hydra 750ml Sports Bottle', 'slug' => 'hydra-750ml-sports-bottle',
                'sku' => 'KZ-WB-001',
                'short_description' => 'Leak-proof 750ml bottle with one-hand flip lid.',
                'description' => 'Stay hydrated on the move. The Hydra is made from BPA-free Tritan with a leak-proof flip lid you can open with one hand — perfect for the gym and the daily commute.',
                'ingredients' => 'BPA-free Tritan, Leak-proof flip lid, One-hand open, Carry loop',
                'price' => 690, 'discount_price' => 590, 'weight' => '750 ml', 'stock' => 120,
                'is_featured' => true, 'tags' => ['bottle', 'gym', 'bpa-free'],
            ],
            [
                'category_id' => $bottles, 'name' => 'Kizora Steel 1L Insulated Bottle', 'slug' => 'steel-1l-insulated-bottle',
                'sku' => 'KZ-WB-002',
                'short_description' => 'Double-wall stainless bottle that keeps drinks cold all day.',
                'description' => 'Food-grade stainless steel with double-wall vacuum insulation and a durable powder-coated finish. Cold for 24 hours, hot for 12.',
                'ingredients' => 'Food-grade stainless steel, Double-wall vacuum, Powder-coated finish, Leak-proof cap',
                'price' => 1290, 'discount_price' => 990, 'weight' => '1 L', 'stock' => 80,
                'is_featured' => true, 'tags' => ['bottle', 'insulated', 'steel'],
            ],
            [
                'category_id' => $bottles, 'name' => 'Kizora Flow 2L Gallon Bottle', 'slug' => 'flow-2l-gallon-bottle',
                'sku' => 'KZ-WB-003',
                'short_description' => 'Big 2L bottle with time markers to track your intake.',
                'description' => 'Hit your hydration goals with the Flow. A 2L BPA-free bottle with motivational time markers and a comfortable carry strap.',
                'ingredients' => 'BPA-free, Carry strap, Time markers, Wide mouth',
                'price' => 890, 'discount_price' => null, 'weight' => '2 L', 'stock' => 70,
                'is_featured' => false, 'tags' => ['bottle', 'gym', 'gallon'],
            ],

            // ── Thermos & Flasks ──
            [
                'category_id' => $flasks, 'name' => 'Kizora Thermo 500ml Flask', 'slug' => 'thermo-500ml-flask',
                'sku' => 'KZ-FL-001',
                'short_description' => 'Compact flask: hot for 12 hours, cold for 24.',
                'description' => 'The Thermo keeps your coffee hot and your water icy. Built from 304 stainless steel with double-wall vacuum insulation and a leak-proof lid that fits any bag.',
                'ingredients' => '304 stainless steel, Double-wall vacuum, Leak-proof lid, Hot 12h / Cold 24h',
                'price' => 1490, 'discount_price' => 1190, 'weight' => '500 ml', 'stock' => 95,
                'is_featured' => true, 'show_on_hero' => true, 'tags' => ['flask', 'thermos', 'insulated', 'bestseller'],
            ],
            [
                'category_id' => $flasks, 'name' => 'Kizora Summit 1L Travel Flask', 'slug' => 'summit-1l-travel-flask',
                'sku' => 'KZ-FL-002',
                'short_description' => 'Rugged 1L flask with cup lid for trips and treks.',
                'description' => 'For longer journeys, the Summit holds a full litre and keeps drinks hot up to 18 hours. The screw cup lid doubles as a mug.',
                'ingredients' => '316 stainless steel, Vacuum insulated, Cup lid, Hot 18h',
                'price' => 1990, 'discount_price' => 1690, 'weight' => '1 L', 'stock' => 55,
                'is_featured' => true, 'tags' => ['flask', 'travel', 'insulated'],
            ],
            [
                'category_id' => $flasks, 'name' => 'Kizora Coffee Tumbler 380ml', 'slug' => 'coffee-tumbler-380ml',
                'sku' => 'KZ-FL-003',
                'short_description' => 'Spill-resistant coffee tumbler for desk and car.',
                'description' => 'A handy 380ml tumbler with a sip lid and anti-slip base — your morning coffee, kept warm from kitchen to desk.',
                'ingredients' => 'Stainless steel, Sip lid, Anti-slip base, Fits cup holders',
                'price' => 990, 'discount_price' => null, 'weight' => '380 ml', 'stock' => 85,
                'is_featured' => false, 'tags' => ['tumbler', 'coffee', 'office'],
            ],

            // ── Accessories ──
            [
                'category_id' => $access, 'name' => 'Kizora Tech Organizer Pouch', 'slug' => 'tech-organizer-pouch',
                'sku' => 'KZ-AC-001',
                'short_description' => 'Keep cables, chargers and adapters tidy and tangle-free.',
                'description' => 'Elastic loops and zip-mesh pockets hold every cable, charger and adapter in place. A water-resistant shell protects it all.',
                'ingredients' => 'Water-resistant shell, Elastic loops, Zip mesh pockets, Grab handle',
                'price' => 590, 'discount_price' => null, 'weight' => 'Cable organizer', 'stock' => 100,
                'is_featured' => false, 'tags' => ['accessory', 'organizer', 'tech'],
            ],
            [
                'category_id' => $access, 'name' => 'Kizora Packing Cubes (Set of 3)', 'slug' => 'packing-cubes-set-of-3',
                'sku' => 'KZ-AC-002',
                'short_description' => 'S/M/L cubes that turn any bag into organized storage.',
                'description' => 'Pack smarter with breathable mesh-top cubes in three sizes. Lightweight, durable and a traveller’s best friend.',
                'ingredients' => 'Lightweight nylon, Breathable mesh tops, Carry handles, Set of 3 (S/M/L)',
                'price' => 1190, 'discount_price' => 990, 'weight' => 'Set of 3 · S/M/L', 'stock' => 60,
                'is_featured' => true, 'tags' => ['accessory', 'travel', 'packing'],
            ],
            [
                'category_id' => $access, 'name' => 'Kizora Slim Travel Sling', 'slug' => 'slim-travel-sling',
                'sku' => 'KZ-AC-003',
                'short_description' => 'A 2.5L crossbody sling with an RFID-safe pocket.',
                'description' => 'Carry the essentials hands-free. The Slim Sling features an adjustable strap and an RFID-blocking pocket to keep cards and passport safe.',
                'ingredients' => 'Water-resistant fabric, Adjustable strap, RFID-blocking pocket',
                'price' => 990, 'discount_price' => null, 'weight' => '2.5 L', 'stock' => 70,
                'is_featured' => false, 'tags' => ['accessory', 'sling', 'travel'],
            ],
            [
                'category_id' => $access, 'name' => 'Kizora Bottle Carry Sleeve', 'slug' => 'bottle-carry-sleeve',
                'sku' => 'KZ-AC-004',
                'short_description' => 'Neoprene sleeve with a strap for bottles up to 1L.',
                'description' => 'Protect your bottle and carry it hands-free. The neoprene sleeve fits 750ml–1L bottles and adds a comfortable shoulder strap.',
                'ingredients' => 'Neoprene, Shoulder strap, Fits 750ml–1L',
                'price' => 390, 'discount_price' => null, 'weight' => 'Fits 750ml–1L', 'stock' => 110,
                'is_featured' => false, 'tags' => ['accessory', 'bottle', 'sleeve'],
            ],
        ];

        foreach ($products as $productData) {
            // Build USPs from the legacy capacity + feature list.
            $usps = [];
            if (! empty($productData['weight'])) {
                $usps[] = ['title' => $productData['weight'], 'text' => 'Size & capacity'];
            }
            foreach (array_filter(array_map('trim', explode(',', $productData['ingredients'] ?? ''))) as $feature) {
                $usps[] = ['title' => $feature, 'text' => ''];
            }
            $productData['usps'] = $usps;
            unset($productData['ingredients'], $productData['weight']);

            Product::create($productData);
        }
    }
}
