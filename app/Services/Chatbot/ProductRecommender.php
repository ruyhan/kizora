<?php

namespace App\Services\Chatbot;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Real-time product search for the chatbot. Always queries live data, so the
 * chatbot "knowledge" is automatically in sync with the catalogue — no retraining.
 */
class ProductRecommender
{
    private const STOP = ['the', 'and', 'for', 'with', 'show', 'me', 'some', 'any', 'best', 'good', 'want', 'need',
        'looking', 'buy', 'get', 'have', 'you', 'can', 'please', 'under', 'below', 'above', 'over', 'around',
        'product', 'products', 'item', 'items', 'price', 'cheap', 'budget', 'recommend', 'suggest', 'find'];

    private const INTENT = ['show', 'recommend', 'suggest', 'looking', 'want', 'need', 'buy', 'best', 'cheap',
        'budget', 'under', 'below', 'price', 'product', 'bag', 'bottle', 'flask', 'backpack', 'gift', 'find', 'do you have'];

    public function looksLikeProductQuery(string $message): bool
    {
        $m = strtolower($message);
        foreach (self::INTENT as $kw) {
            if (str_contains($m, $kw)) {
                return true;
            }
        }
        return false;
    }

    public function search(string $query, int $limit = 8): Collection
    {
        $q = strtolower(trim($query));

        $max = null;
        $min = null;
        if (preg_match('/(under|below|less than|within|max|upto|up to)\D{0,6}(\d{2,7})/', $q, $m)) {
            $max = (int) $m[2];
        }
        if (preg_match('/(above|over|more than|min|from)\D{0,6}(\d{2,7})/', $q, $m)) {
            $min = (int) $m[2];
        }

        $clean = preg_replace('/[^a-z0-9 ]/', ' ', $q);
        $words = array_values(array_filter(explode(' ', $clean), fn ($w) => strlen($w) >= 3 && ! in_array($w, self::STOP, true) && ! ctype_digit($w)));

        $builder = Product::where('is_active', true)->with('category:id,name')
            ->when($max, fn ($b) => $b->where('price', '<=', $max))
            ->when($min, fn ($b) => $b->where('price', '>=', $min));

        if (! empty($words)) {
            $builder->where(function ($b) use ($words) {
                foreach ($words as $w) {
                    $b->orWhere('name', 'like', "%{$w}%")
                      ->orWhere('short_description', 'like', "%{$w}%")
                      ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$w}%"))
                      ->orWhereJsonContains('tags', $w);
                }
            });
        }

        $products = $builder->orderByDesc('is_featured')->limit($limit)->get();

        // Fallback to featured/popular when nothing specific matched.
        if ($products->isEmpty()) {
            $products = Product::where('is_active', true)->with('category:id,name')
                ->orderByDesc('is_featured')->latest()->limit($limit)->get();
        }

        return $products;
    }

    public function format(Collection $products): array
    {
        return $products->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'price' => (float) $p->final_price,
            'compare_at' => $p->discount_price ? (float) $p->price : null,
            'image' => $p->featured_image_url,
            'category' => $p->category?->name,
            'in_stock' => $p->stock > 0,
        ])->all();
    }
}
