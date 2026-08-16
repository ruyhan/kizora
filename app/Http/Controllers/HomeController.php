<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\OrderItem;
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
        $page = $this->content->get('home');

        // "Shop by Category" — admin-curated featured categories only (max 8, by featured order).
        // Fully dynamic from the Category module; cache busts whenever a category is saved.
        $categories = $this->cache->remember('home:categories', fn () => Category::featuredForHome(8));

        // Admin-managed product sections (Pages & Content → Home page), in admin order.
        $sections = collect($page['sections'] ?? [])
            ->filter(fn ($s) => is_array($s) && ! empty($s['active']))
            ->values();

        return Inertia::render('Home', [
            'page'         => $page,
            'categories'   => $categories,
            // Headings render immediately (skeleton fallback) …
            'sectionHeads' => $sections->map(fn ($s) => $this->sectionMeta($s))->all(),
            // … products stream after first paint (deferred) + cached per section.
            'sections'     => Inertia::defer(fn () => $sections->map(fn ($s) => array_merge(
                $this->sectionMeta($s),
                ['products' => $this->sectionProducts($s)],
            ))->all()),
            // Blog slider (bottom of the page) — deferred + cached; empty when disabled.
            'blogPosts'    => Inertia::defer(fn () => $this->blogPosts($page)),
        ]);
    }

    /** Latest published blog posts for the home-page slider — cached; empty when disabled. */
    private function blogPosts(array $page): array
    {
        if (empty($page['blog_enabled'])) {
            return [];
        }
        $limit = max(1, min(20, (int) ($page['blog_limit'] ?? 8) ?: 8));

        return $this->cache->remember('home:blog:'.$limit, fn () => BlogPost::published()
            ->with('category:id,name,slug')
            ->latest('published_at')->latest('id')
            ->take($limit)->get()
            ->map(fn ($p) => [
                'id'                 => $p->id,
                'title'              => $p->title,
                'slug'               => $p->slug,
                'excerpt'            => $p->excerpt,
                'featured_image_url' => $p->featured_image_url,
                'reading_time'       => $p->estimatedReadingTime(),
                'published_at'       => $p->published_at?->format('d M Y'),
                'category'           => $p->category ? ['name' => $p->category->name, 'slug' => $p->category->slug] : null,
            ])->all());
    }

    /** Presentation meta for a home section (cheap — read straight from content JSON). */
    private function sectionMeta(array $s): array
    {
        $params = [];
        if (! empty($s['category'])) {
            $params['category'] = $s['category'];
        }
        $params += match ($s['sort'] ?? 'latest') {
            'price_asc'    => ['sort' => 'price_asc'],
            'price_desc'   => ['sort' => 'price_desc'],
            'best_selling' => ['sort' => 'best_selling'],
            'featured'     => ['sort' => 'popular'],
            'top_rated'    => ['sort' => 'rating'],
            'on_sale'      => ['sale' => 1],
            'new_arrival'  => ['sort' => 'latest'],
            'latest'       => ['sort' => 'latest'],
            default        => [],
        };

        return [
            'title'    => (string) ($s['title'] ?? ''),
            'subtitle' => (string) ($s['subtitle'] ?? ''),
            'badge'    => (string) ($s['badge'] ?? ''),
            'layout'   => ($s['layout'] ?? 'rail') === 'grid' ? 'grid' : 'rail',
            'view_all' => route('shop.index', $params),
        ];
    }

    /**
     * Products for a home section — cached; the key hashes the section's query config.
     * A section with "random order" on is deliberately NOT cached: it has to return a
     * different mix on every page load.
     */
    private function sectionProducts(array $s): array
    {
        $sort   = $s['sort'] ?? 'latest';
        $limit  = max(1, min(24, (int) ($s['limit'] ?? 10) ?: 10));
        $random = ! empty($s['random_order']);
        $key    = 'home:section:'.md5(json_encode([$s['category'] ?? '', $sort, $limit]));

        $build = function () use ($s, $sort, $limit, $random) {
            $query = $this->base();

            if (! empty($s['category'])) {
                $cat = Category::where('slug', $s['category'])->first();
                if (! $cat) {
                    return []; // stale slug — section hides itself
                }
                // A parent category aggregates all of its sub-categories' products.
                $query->whereIn('category_id', $cat->descendantIds());
            }

            match ($sort) {
                'price_asc'    => $query->orderBy('price'),
                'price_desc'   => $query->orderByDesc('price'),
                'best_selling' => $query->orderByDesc(
                    OrderItem::selectRaw('coalesce(sum(quantity), 0)')
                        ->whereColumn('order_items.product_id', 'products.id')
                )->latest(),
                'featured'     => $query->where('is_featured', true)->latest(),
                'on_sale'      => $query->onSale()->latest(),
                'new_arrival'  => $query->where('show_on_hero', true)->latest(),
                'top_rated'    => $query->orderByDesc('reviews_avg_rating')->latest(),
                'random'       => $query->inRandomOrder(),
                default        => $query->latest(),
            };

            // Keep the source filter (e.g. New Arrival only) but drop its ordering,
            // so each load draws a fresh random slice from the whole matching set.
            if ($random) {
                $query->reorder()->inRandomOrder();
            }

            return $query->take($limit)->get()->map(fn ($p) => $this->formatProduct($p))->all();
        };

        return $random ? $build() : $this->cache->remember($key, $build);
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
        return Inertia::render('Faq', ['faqs' => $faq['items'] ?? [], 'page' => $faq]);
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
