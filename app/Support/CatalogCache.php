<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for catalog reads. bust() bumps a version integer so every
 * previously-cached key is instantly orphaned (no tag support needed — works
 * on the file/database cache stores).
 */
class CatalogCache
{
    private const VERSION_KEY = 'catalog:version';

    public function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public function remember(string $key, Closure $callback, int $ttl = 3600): mixed
    {
        return Cache::remember("catalog:v{$this->version()}:{$key}", $ttl, $callback);
    }

    public function bust(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }
}
