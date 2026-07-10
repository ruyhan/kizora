<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Idempotent — don't duplicate the samples on re-run.
        if (BlogPost::count() > 0) {
            $this->command?->warn('Blog posts already exist — skipping sample seed.');
            return;
        }

        // A pool of real Media Library images (root-relative, host-safe URLs).
        $images = MediaFile::where('mime_type', 'like', 'image/%')
            ->where('original_name', 'not like', '%logo%')
            ->where('original_name', 'not like', '%favicon%')
            ->orderBy('id')
            ->pluck('file_path')
            ->map(fn ($p) => '/storage/' . ltrim($p, '/'))
            ->values();

        $img = fn (int $i) => $images->get($i % max(1, $images->count())) ?? null;

        $author = User::whereHas('role', fn ($q) => $q->where('is_staff', true))->first();

        // Categories
        $guides = BlogCategory::create(['name' => 'Guides', 'slug' => 'guides', 'description' => 'Practical how-tos and buying guides.', 'sort_order' => 1]);
        $tips   = BlogCategory::create(['name' => 'Tips & Care', 'slug' => 'tips-care', 'description' => 'Keep your gear in top shape.', 'sort_order' => 2]);
        $stories = BlogCategory::create(['name' => 'Stories', 'slug' => 'stories', 'description' => 'Life on the move with Kizora.', 'sort_order' => 3]);

        $posts = [
            [
                'title'    => 'How to Choose the Perfect Backpack for Work & Study',
                'category' => $guides,
                'featured' => true,
                'tags'     => ['backpacks', 'buying guide', 'work'],
                'excerpt'  => 'From laptop sleeves to water-resistant fabrics — here is everything to look for before buying your next everyday carry.',
                'content'  => '<p>Your backpack is the one thing you carry every single day, so it pays to choose well. In this guide we break down the features that actually matter.</p>'
                    . '<h2>1. Size &amp; capacity</h2><p>For daily commuting, a <strong>20–25 litre</strong> pack hits the sweet spot: enough for a laptop, charger, water bottle and a light jacket without feeling bulky.</p>'
                    . '<h2>2. Laptop protection</h2><p>Look for a dedicated, padded sleeve that sits slightly off the bottom of the bag so a knock never reaches your device.</p>'
                    . '<h2>3. Comfort</h2><p>Wide, breathable shoulder straps and a padded back panel make a heavy load feel lighter on long days.</p>'
                    . '<blockquote>A great backpack disappears on your back — you only notice a bad one.</blockquote>'
                    . '<p>Browse the Kizora range to find a pack built for the way you move.</p>',
                'reading_time' => 5,
            ],
            [
                'title'    => 'Keep Your Water Bottle Fresh: A Simple Care Routine',
                'category' => $tips,
                'featured' => false,
                'tags'     => ['bottles', 'care', 'tips'],
                'excerpt'  => 'A few minutes of care keeps your insulated bottle tasting clean and performing like new for years.',
                'content'  => '<p>Insulated stainless-steel bottles are built to last — but only if you look after them. Here is the routine we recommend.</p>'
                    . '<h2>Daily rinse</h2><p>Rinse with warm water after every use, especially after drinks other than water.</p>'
                    . '<h2>Weekly deep clean</h2><p>Fill with warm water, add a spoon of baking soda, and let it sit for 15 minutes before scrubbing with a bottle brush.</p>'
                    . '<h2>Mind the lid</h2><p>Lids and seals trap the most residue. Take them apart and wash the gasket separately.</p>'
                    . '<p>Treat your bottle well and it will keep your drinks hot or cold for hours, every day.</p>',
                'reading_time' => 3,
            ],
            [
                'title'    => 'On the Move: Packing Light for a Weekend Away',
                'category' => $stories,
                'featured' => false,
                'tags'     => ['travel', 'packing', 'lifestyle'],
                'excerpt'  => 'One bag, two days, zero stress. Our team shares the packing philosophy behind a perfect weekend escape.',
                'content'  => '<p>The secret to a great weekend trip is not packing more — it is packing smarter. Here is how we do it with a single Kizora pack.</p>'
                    . '<h2>Roll, do not fold</h2><p>Rolled clothes take less space and wrinkle less. You will fit surprisingly more.</p>'
                    . '<h2>Wear the bulky stuff</h2><p>Travel in your heaviest shoes and jacket to free up room inside the bag.</p>'
                    . '<h2>One bottle, always</h2><p>A reusable bottle keeps you hydrated on the road and saves buying plastic along the way.</p>'
                    . '<p>Pack light, move freely, and make the most of every weekend.</p>',
                'reading_time' => 4,
            ],
        ];

        $i = 0;
        foreach ($posts as $p) {
            $post = BlogPost::create([
                'blog_category_id' => $p['category']->id,
                'user_id'          => $author?->id,
                'author_name'      => $author?->name ?? 'Kizora Team',
                'title'            => $p['title'],
                'slug'             => Str::slug($p['title']),
                'excerpt'          => $p['excerpt'],
                'content'          => $p['content'],
                'featured_image'   => $img($i),
                'content_images'   => array_values(array_filter([$img($i + 1), $img($i + 2)])),
                'meta_title'       => $p['title'] . ' | Kizora Blog',
                'meta_description' => $p['excerpt'],
                'meta_keywords'    => implode(', ', $p['tags']),
                'og_image'         => $img($i),
                'status'           => 'published',
                'is_featured'      => $p['featured'],
                'reading_time'     => $p['reading_time'],
                'published_at'     => now()->subDays(($i + 1) * 3),
            ]);

            $tagIds = collect($p['tags'])->map(fn ($t) => BlogTag::findOrCreateByName($t)->id)->all();
            $post->tags()->sync($tagIds);

            $i += 2;
        }

        $this->command?->info('Seeded ' . count($posts) . ' sample blog posts.');
    }
}
