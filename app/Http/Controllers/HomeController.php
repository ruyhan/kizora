<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogCache;
use App\Support\PageContent;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly CatalogCache $cache,
        private readonly PageContent $content,
    ) {}

    /** Narrowed base query — only the columns the storefront cards need. */
    private function base()
    {
        return Product::query()
            ->select(['id', 'name', 'slug', 'sku', 'price', 'discount_price', 'sale_starts_at', 'sale_ends_at', 'featured_image', 'short_description', 'stock', 'is_featured', 'category_id', 'has_variations', 'variations', 'usps'])
            ->with('category:id,name,slug')
            ->with('firstImage')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('is_active', true);
    }

    public function index(): Response
    {
        $hero = $this->cache->remember('home:hero', fn () => $this->base()
            ->where('show_on_hero', true)->latest()->take(3)->get()
            ->map(fn ($p) => $this->formatProduct($p))->all());

        $featured = $this->cache->remember('home:featured', fn () => $this->base()
            ->where('is_featured', true)->latest()->take(8)->get()
            ->map(fn ($p) => $this->formatProduct($p))->all());

        // "Shop by Category" — admin-curated featured categories only (max 8, by featured order).
        // Fully dynamic from the Category module; cache busts whenever a category is saved.
        $categories = $this->cache->remember('home:categories', fn () => Category::featuredForHome(8));

        return Inertia::render('Home', [
            'page'             => $this->content->get('home'),
            'heroProducts'     => $hero,
            'featuredProducts' => $featured,
            'categories'       => $categories,
            // Below-the-fold rails: deferred (stream after first paint) + cached.
            'newArrivals'      => Inertia::defer(fn () => $this->cache->remember('home:new', fn () => $this->base()
                ->latest()->take(10)->get()->map(fn ($p) => $this->formatProduct($p))->all())),
            'onSale'           => Inertia::defer(fn () => $this->cache->remember('home:sale', fn () => $this->base()
                ->onSale()->latest()->take(10)->get()->map(fn ($p) => $this->formatProduct($p))->all())),
            'popularProducts'  => Inertia::defer(fn () => $this->cache->remember('home:popular', fn () => $this->base()
                ->inRandomOrder()->take(10)->get()->map(fn ($p) => $this->formatProduct($p))->all())),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('About', ['page' => $this->content->get('about')]);
    }

    public function contact(): Response
    {
        return Inertia::render('Contact', ['page' => $this->content->get('contact')]);
    }

    public function faq(): Response
    {
        $faq = $this->content->get('faqs');
        return Inertia::render('Faq', ['faqs' => $faq['items'] ?? []]);
    }

    public function privacy(): Response
    {
        return Inertia::render('PrivacyPolicy', ['page' => $this->content->get('privacy')]);
    }

    public function terms(): Response
    {
        return Inertia::render('Terms', ['page' => $this->content->get('terms')]);
    }

    private function formatProduct(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'final_price' => $product->final_price,
            'discount_percentage' => $product->discount_percentage,
            'featured_image_url' => $product->featured_image_url,
            'hover_image' => $product->firstImage?->image_url,
            'short_description' => $product->short_description,
            'category' => [
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ],
            'stock' => $product->stock,
            'is_featured' => $product->is_featured,
            'usps' => $product->usps ?? [],
            'has_variations' => $product->has_variations,
            'variations' => $product->variations ?? [],
            'average_rating' => round($product->reviews_avg_rating ?? 0, 1),
            'review_count'   => $product->reviews_count ?? 0,
        ];
    }
}
