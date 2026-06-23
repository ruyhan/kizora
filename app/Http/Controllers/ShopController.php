<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\CatalogCache;
use App\Support\PageContent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function __construct(
        private readonly CatalogCache $cache,
        private readonly PageContent $content,
    ) {}

    public function index(Request $request): Response
    {
        $query = Product::query()
            ->select(['id', 'name', 'slug', 'price', 'discount_price', 'featured_image', 'short_description', 'stock', 'is_featured', 'category_id', 'has_variations', 'variations', 'usps'])
            ->with('category:id,name,slug')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('is_active', true);

        if ($request->filled('category')) {
            $cat = Category::where('slug', $request->category)->first();
            if ($cat) {
                // A parent category aggregates all of its sub-categories' products.
                $query->whereIn('category_id', $cat->descendantIds());
            } else {
                $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
            }
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('short_description', 'like', "%{$request->search}%");
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Availability
        if ($request->boolean('in_stock')) {
            $query->where('stock', '>', 0);
        }

        // On sale
        if ($request->boolean('sale')) {
            $query->whereNotNull('discount_price');
        }

        // Tag facets (color / material) — OR within selected tags
        $tags = array_filter((array) $request->input('tags', []));
        if (! empty($tags)) {
            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Minimum rating (correlated subquery — robust on MySQL/MariaDB without GROUP BY)
        if ($request->filled('rating')) {
            $query->whereRaw(
                '(select coalesce(avg(rating), 0) from reviews where reviews.product_id = products.id) >= ?',
                [(float) $request->rating]
            );
        }

        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'best_selling' => $query->orderByDesc('reviews_count')->latest(),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            'popular' => $query->where('is_featured', true)->latest(),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->through(fn($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'price' => $p->price,
            'discount_price' => $p->discount_price,
            'final_price' => $p->final_price,
            'discount_percentage' => $p->discount_percentage,
            'featured_image_url' => $p->featured_image_url,
            'short_description' => $p->short_description,
            'usps'           => $p->usps ?? [],
            'category'       => ['name' => $p->category->name, 'slug' => $p->category->slug],
            'stock'          => $p->stock,
            'average_rating' => round($p->reviews_avg_rating ?? 0, 1),
            'review_count'   => $p->reviews_count ?? 0,
            'has_variations' => $p->has_variations,
            'variations'     => $p->variations ?? [],
        ]);

        // Filter-independent facets — cached (busted on product/category save).
        $categories = $this->cache->remember('shop:categories', fn () => Category::where('is_active', true)->orderBy('sort_order')
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->get(['id', 'name', 'slug'])->all());

        $priceBounds = $this->cache->remember('shop:price_bounds', fn () => [
            'min' => (int) floor(Product::where('is_active', true)->min('price') ?? 0),
            'max' => (int) ceil(Product::where('is_active', true)->max('price') ?? 0),
        ]);

        $availableTags = $this->cache->remember('shop:tags', fn () => Product::where('is_active', true)->pluck('tags')
            ->flatten()->filter()->unique()->sort()->values()->all());

        return Inertia::render('Shop', [
            'page'          => $this->content->get('shop'),
            'products'      => $products,
            'categories'    => $categories,
            'priceBounds'   => $priceBounds,
            'availableTags' => $availableTags,
            'filters'       => array_merge(
                $request->only(['category', 'search', 'sort', 'min_price', 'max_price', 'rating']),
                [
                    'in_stock' => $request->boolean('in_stock'),
                    'sale'     => $request->boolean('sale'),
                    'tags'     => $tags,
                ]
            ),
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $products = Product::with('category')
            ->where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('short_description', 'like', "%{$q}%");
            })
            ->take(6)
            ->get()
            ->map(fn($p) => [
                'id'                 => $p->id,
                'name'               => $p->name,
                'slug'               => $p->slug,
                'price'              => $p->price,
                'final_price'        => $p->final_price,
                'featured_image_url' => $p->featured_image_url,
                'category'           => $p->category?->name,
            ]);

        return response()->json($products);
    }

    public function quickview(string $slug): \Illuminate\Http\JsonResponse
    {
        $p = Product::with('category')->withAvg('reviews', 'rating')->withCount('reviews')
            ->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return response()->json([
            'id'                  => $p->id,
            'name'                => $p->name,
            'slug'                => $p->slug,
            'price'               => $p->price,
            'discount_price'      => $p->discount_price,
            'final_price'         => $p->final_price,
            'discount_percentage' => $p->discount_percentage,
            'featured_image_url'  => $p->featured_image_url,
            'short_description'   => $p->short_description,
            'usps'                => $p->usps ?? [],
            'stock'               => $p->stock,
            'tags'                => $p->tags,
            'has_variations'      => $p->has_variations,
            'variations'          => $p->variations ?? [],
            'category'            => ['name' => $p->category->name, 'slug' => $p->category->slug],
            'average_rating'      => round($p->reviews_avg_rating ?? 0, 1),
            'review_count'        => $p->reviews_count ?? 0,
        ]);
    }

    public function show(string $slug): Response
    {
        $product = Product::with(['category', 'images', 'reviews.user'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $related = Product::with('category')
            ->withAvg('reviews', 'rating')->withCount('reviews')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'price' => $p->price,
                'discount_price' => $p->discount_price,
                'final_price' => $p->final_price,
                'discount_percentage' => $p->discount_percentage,
                'featured_image_url' => $p->featured_image_url,
                'short_description' => $p->short_description,
                'usps' => $p->usps ?? [],
                'category' => ['name' => $p->category->name, 'slug' => $p->category->slug],
                'average_rating' => round($p->reviews_avg_rating ?? 0, 1),
                'review_count'   => $p->reviews_count ?? 0,
                'has_variations' => $p->has_variations,
                'variations'     => $p->variations ?? [],
            ]);

        $userHasReviewed = auth()->check()
            ? \App\Models\Review::where('product_id', $product->id)
                ->where('user_id', auth()->id())
                ->exists()
            : false;

        // Delivery availability based on the logged-in user's saved city
        $availability = ['status' => 'unknown', 'city_name' => null];
        if (auth()->check() && auth()->user()->delivery_area_id) {
            $area = \App\Models\DeliveryArea::with('city')->find(auth()->user()->delivery_area_id);
            $city = $area?->city;
            if ($city && $city->is_active) {
                $availability = ['status' => 'available', 'city_name' => $city->name];
            } elseif ($city) {
                $availability = ['status' => 'unavailable', 'city_name' => $city->name];
            }
        }

        return Inertia::render('ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'discount_price' => $product->discount_price,
                'final_price' => $product->final_price,
                'discount_percentage' => $product->discount_percentage,
                'featured_image_url' => $product->featured_image_url,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'usps' => $product->usps ?? [],
                'has_variations' => $product->has_variations,
                'variations' => $product->variations ?? [],
                'sku' => $product->sku,
                'stock' => $product->stock,
                'tags' => $product->tags,
                'average_rating' => $product->average_rating,
                'user_has_reviewed' => $userHasReviewed,
                'images' => $product->images->map(fn($i) => ['image_url' => $i->image_url]),
                'category' => ['name' => $product->category->name, 'slug' => $product->category->slug],
                'reviews' => $product->reviews->map(fn($r) => [
                    'id' => $r->id,
                    'rating' => $r->rating,
                    'comment' => $r->comment,
                    'created_at' => $r->created_at->diffForHumans(),
                    'user' => ['name' => $r->user->name],
                ]),
            ],
            'related'      => $related,
            'availability' => $availability,
        ]);
    }
}
