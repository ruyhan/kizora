<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect([
            route('home'), route('shop.index'), route('about'),
            route('contact'), route('faq'), route('privacy'), route('terms'),
        ]);

        $urls = $urls
            ->merge(Category::where('is_active', true)->pluck('slug')->map(fn ($s) => route('shop.index', ['category' => $s])))
            ->merge(Product::where('is_active', true)->pluck('slug')->map(fn ($s) => route('shop.show', $s)));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . e($u) . '</loc></url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
