<?php

use App\Support\CatalogCache;
use Illuminate\Database\Migrations\Migration;

/**
 * The product/category image accessors switched from asset() (which baked the
 * host, e.g. http://127.0.0.1:8000/…) to root-relative /storage/… URLs. The home
 * page and shop cache the formatted product data via CatalogCache, so any
 * previously-cached entries still hold the OLD absolute URLs. Bump the cache
 * version here so those entries are orphaned and regenerated on the next request
 * — runs automatically on `php artisan migrate` at deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            app(CatalogCache::class)->bust();
        } catch (\Throwable $e) {
            // Cache store unavailable during migrate — a manual `cache:clear` covers it.
        }
    }

    public function down(): void
    {
        //
    }
};
