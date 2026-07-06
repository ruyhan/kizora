<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        $bust = fn () => app(CatalogCache::class)->bust();
        static::saved($bust);
        static::deleted($bust);
    }

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'short_description',
        'description', 'usps', 'price', 'discount_price',
        'stock', 'featured_image', 'is_featured', 'show_on_hero', 'is_active', 'tags',
        'has_variations', 'variations',
    ];

    protected $casts = [
        'is_featured'   => 'boolean',
        'show_on_hero'  => 'boolean',
        'is_active'     => 'boolean',
        'has_variations' => 'boolean',
        'tags' => 'array',
        'usps' => 'array',
        'variations' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
    ];

    protected $appends = ['final_price', 'discount_percentage', 'featured_image_url'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    /** First gallery image — shown on product cards when hovering. */
    public function firstImage()
    {
        return $this->hasOne(ProductImage::class)->ofMany('sort_order', 'min');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->discount_price) return 0;
        return (int) round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    public function getFeaturedImageUrlAttribute(): string
    {
        return $this->featured_image
            ? asset('storage/' . $this->featured_image)
            : asset('images/product-placeholder.svg');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }
}
