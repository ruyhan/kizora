<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        $bust = fn () => app(CatalogCache::class)->bust();
        static::saved($bust);
        static::deleted($bust);
    }

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'image', 'icon', 'is_active', 'sort_order',
        'is_featured', 'featured_sort_order',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** This category's id plus all descendant ids (one extra level deep). */
    public function descendantIds(): array
    {
        return $this->children()->pluck('id')->push($this->id)->all();
    }

    /**
     * Active category tree (parents → children) with aggregated product counts.
     * Used by the mega menu, mobile drawer and shop facet.
     */
    public static function navTree(): array
    {
        $all = static::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'slug', 'parent_id', 'icon']);

        return $all->whereNull('parent_id')->map(function ($parent) use ($all) {
            $children = $all->where('parent_id', $parent->id)->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'slug' => $c->slug,
                'icon' => $c->icon, 'count' => $c->products_count,
            ])->values();

            return [
                'id'       => $parent->id,
                'name'     => $parent->name,
                'slug'     => $parent->slug,
                'icon'     => $parent->icon,
                'count'    => $parent->products_count + $children->sum('count'),
                'children' => $children->all(),
            ];
        })->values()->all();
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? asset('storage/' . $this->image)
            : asset('images/categories/default.jpg');
    }

    /**
     * Featured categories for the homepage "Shop by Category" section.
     * Active + featured only, ordered by the admin-controlled featured sort order,
     * capped at $limit (homepage shows the top 8). Driven entirely from the DB.
     */
    public static function featuredForHome(int $limit = 8): array
    {
        return static::where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('featured_sort_order')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take($limit)
            ->get(['id', 'name', 'slug', 'description', 'icon', 'image'])
            ->map(fn ($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'slug'           => $c->slug,
                'description'    => $c->description,
                'icon'          => $c->icon,
                'image_url'      => $c->image ? asset('storage/' . $c->image) : null,
                'products_count' => Product::where('is_active', true)->whereIn('category_id', $c->descendantIds())->count(),
            ])->all();
    }
}
