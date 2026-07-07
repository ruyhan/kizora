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
        'sale_starts_at', 'sale_ends_at', 'meta_title', 'meta_description',
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
        'sale_starts_at' => 'date',
        'sale_ends_at' => 'date',
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

    /** Whether the sale price currently applies (no dates = always, as before). */
    public function saleIsActive(): bool
    {
        if (! $this->discount_price) return false;
        if ($this->sale_starts_at && now()->lt($this->sale_starts_at->copy()->startOfDay())) return false;
        if ($this->sale_ends_at && now()->gt($this->sale_ends_at->copy()->endOfDay())) return false;
        return true;
    }

    /** Products whose sale price applies right now (schedule-aware). */
    public function scopeOnSale($query)
    {
        return $query->whereNotNull('discount_price')
            ->where(fn ($q) => $q->whereNull('sale_starts_at')->orWhereDate('sale_starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhereDate('sale_ends_at', '>=', now()));
    }

    public function getFinalPriceAttribute(): float
    {
        return $this->saleIsActive() ? $this->discount_price : $this->price;
    }

    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->saleIsActive()) return 0;
        return (int) round((($this->price - $this->discount_price) / $this->price) * 100);
    }

    /* ── Per-variation price & stock ──────────────────────────────────────
       Options may carry an optional `price` (± adjustment on the final price)
       and an optional `stock` counter. Selections are matched by group name +
       option value, mirroring the cart line payload. */

    /** Sum of price adjustments for a selected variation set. */
    public function variationPriceDelta(?array $selected): float
    {
        if (! $this->has_variations || empty($selected) || empty($this->variations)) return 0.0;
        $delta = 0.0;
        foreach ($selected as $sel) {
            foreach ($this->variations as $group) {
                if (($group['name'] ?? null) !== ($sel['name'] ?? null)) continue;
                foreach ($group['options'] ?? [] as $opt) {
                    if (($opt['value'] ?? null) === ($sel['value'] ?? null)
                        && isset($opt['price']) && $opt['price'] !== '' && $opt['price'] !== null) {
                        $delta += (float) $opt['price'];
                    }
                }
            }
        }
        return $delta;
    }

    /** Unit price for a specific variation selection. */
    public function priceForVariations(?array $selected): float
    {
        return max(0, $this->final_price + $this->variationPriceDelta($selected));
    }

    /** Sellable quantity for a selection — min(product stock, any per-option stock). */
    public function availableStockFor(?array $selected): int
    {
        $available = (int) $this->stock;
        if (! $this->has_variations || empty($selected) || empty($this->variations)) return $available;
        foreach ($selected as $sel) {
            foreach ($this->variations as $group) {
                if (($group['name'] ?? null) !== ($sel['name'] ?? null)) continue;
                foreach ($group['options'] ?? [] as $opt) {
                    if (($opt['value'] ?? null) === ($sel['value'] ?? null)
                        && isset($opt['stock']) && $opt['stock'] !== '' && $opt['stock'] !== null) {
                        $available = min($available, (int) $opt['stock']);
                    }
                }
            }
        }
        return max(0, $available);
    }

    /** Decrement per-option stock counters after a sale (product stock is decremented separately). */
    public function decrementVariationStock(?array $selected, int $qty): void
    {
        if (! $this->has_variations || empty($selected) || empty($this->variations)) return;
        $vars = $this->variations;
        $changed = false;
        foreach ($selected as $sel) {
            foreach ($vars as $gi => $group) {
                if (($group['name'] ?? null) !== ($sel['name'] ?? null)) continue;
                foreach ($group['options'] ?? [] as $oi => $opt) {
                    if (($opt['value'] ?? null) === ($sel['value'] ?? null)
                        && isset($opt['stock']) && $opt['stock'] !== '' && $opt['stock'] !== null) {
                        $vars[$gi]['options'][$oi]['stock'] = max(0, (int) $opt['stock'] - $qty);
                        $changed = true;
                    }
                }
            }
        }
        if ($changed) $this->forceFill(['variations' => $vars])->save();
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
