<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductGallerySeeder extends Seeder
{
    /**
     * Creates 4 simple colour-block placeholder images and assigns 2–4
     * of them to every product that does not already have gallery images.
     * Admin can replace these with real photos at any time.
     */
    public function run(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->command->warn('GD library not available — skipping gallery placeholders.');
            return;
        }

        Storage::disk('public')->makeDirectory('products/gallery');

        $placeholders = $this->ensurePlaceholders();

        if (empty($placeholders)) {
            $this->command->warn('Could not create placeholder images.');
            return;
        }

        $count = 0;
        foreach (Product::all() as $product) {
            if ($product->images()->exists()) continue; // already has gallery

            // Rotate through the placeholder set so thumbnails differ per product
            $offset = $product->id % count($placeholders);
            $assign = array_slice(array_merge(
                array_slice($placeholders, $offset),
                array_slice($placeholders, 0, $offset)
            ), 0, 3);

            foreach ($assign as $i => $path) {
                ProductImage::firstOrCreate(
                    ['product_id' => $product->id, 'image' => $path],
                    ['sort_order' => $i]
                );
            }
            $count++;
        }

        $this->command->info("Gallery placeholders added to {$count} products.");
    }

    // ── Create 4 coloured placeholder JPEGs and return their storage paths ──

    private function ensurePlaceholders(): array
    {
        $palettes = [
            ['name' => 'ph_ink.jpg',      'bg' => [23, 24, 27],    'label' => 'Front'],
            ['name' => 'ph_slate.jpg',    'bg' => [60, 61, 66],    'label' => 'Detail'],
            ['name' => 'ph_gray.jpg',     'bg' => [107, 107, 115], 'label' => 'Side'],
            ['name' => 'ph_accent.jpg',   'bg' => [237, 28, 36],   'label' => 'In use'],
        ];

        $paths = [];
        foreach ($palettes as $p) {
            $storagePath = 'products/gallery/' . $p['name'];
            $fullPath    = storage_path('app/public/' . $storagePath);

            if (! file_exists($fullPath)) {
                $img = imagecreatetruecolor(600, 600);
                [$r, $g, $b] = $p['bg'];

                // Background
                $bg  = imagecolorallocate($img, $r, $g, $b);
                imagefill($img, 0, 0, $bg);

                // Lighter inner block
                $lite = imagecolorallocate($img, min(255, $r + 40), min(255, $g + 40), min(255, $b + 40));
                imagefilledrectangle($img, 80, 80, 520, 520, $lite);

                // Text
                $white = imagecolorallocate($img, 255, 255, 255);
                $label = $p['label'];
                $x     = (int) ((600 - strlen($label) * 9) / 2);
                imagestring($img, 5, $x, 275, $label, $white);

                imagejpeg($img, $fullPath, 88);
                imagedestroy($img);
            }
            $paths[] = $storagePath;
        }

        return $paths;
    }
}
