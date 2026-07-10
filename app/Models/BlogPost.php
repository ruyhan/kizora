<?php

namespace App\Models;

use App\Support\CatalogCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected static function booted(): void
    {
        // Keep the home-page blog slider cache fresh when posts change.
        $bust = fn () => app(CatalogCache::class)->bust();
        static::saved($bust);
        static::deleted($bust);
    }

    protected $fillable = [
        'blog_category_id', 'user_id', 'title', 'slug', 'author_name',
        'excerpt', 'content', 'featured_image', 'content_images',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image',
        'status', 'is_featured', 'reading_time', 'published_at',
    ];

    protected $casts = [
        'content_images' => 'array',
        'is_featured'    => 'boolean',
        'published_at'   => 'datetime',
    ];

    protected $appends = ['featured_image_url', 'og_image_url'];

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    /** Published posts whose publish date has passed (or is unset). */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Resolve a stored image path (media-library root-relative OR a storage path) to a URL. */
    public static function imageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path; // already a URL / root-relative media path
        }
        return '/storage/' . ltrim($path, '/');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return self::imageUrl($this->featured_image);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return self::imageUrl($this->og_image ?: $this->featured_image);
    }

    /** Estimate reading time (words / 200) when not set explicitly. */
    public function estimatedReadingTime(): int
    {
        if ($this->reading_time) {
            return (int) $this->reading_time;
        }
        $words = str_word_count(strip_tags((string) $this->content));
        return max(1, (int) ceil($words / 200));
    }
}
