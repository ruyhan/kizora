<?php

namespace App\Support;

use Illuminate\Support\Str;

class Media
{
    /**
     * Root-relative public URL for a stored image path (e.g. "media/x.jpg" →
     * "/storage/media/x.jpg"). Root-relative on purpose: the browser resolves it
     * against whatever domain the site is served from, so images work on ANY host
     * (localhost + production) with no dependency on APP_URL — and never trigger the
     * "access 127.0.0.1 / other apps on this device" prompt after a migration.
     *
     * Absolute URLs and already-root-relative paths pass through unchanged.
     * Returns $fallback (typically another root-relative path) when $path is empty.
     */
    public static function url(?string $path, ?string $fallback = null): ?string
    {
        if (! $path) {
            return $fallback;
        }
        if (Str::startsWith($path, ['http://', 'https://', '//', '/'])) {
            return $path;
        }
        return '/storage/' . ltrim($path, '/');
    }
}
