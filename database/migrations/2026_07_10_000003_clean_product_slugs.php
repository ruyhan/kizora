<?php

use App\Models\Product;
use App\Support\CatalogCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * One-time cleanup of auto-generated product slugs. Historically slugs were
 * created as `str-slug(name)-XXXX` (random suffix) and duplicated products got
 * a stale `oldname-copy-XXXX`. This regenerates every slug cleanly from the
 * product's current name (deduplicated) so URLs read like /shop/<product-name>.
 * Direct DB writes — no model events, `updated_at` left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pass 1 — park every slug at a guaranteed-unique temporary value so the
        // clean assignments in pass 2 can never collide with an old slug that a
        // not-yet-processed row still holds (e.g. a stale duplicate slug).
        foreach (Product::orderBy('id')->pluck('id') as $id) {
            DB::table('products')->where('id', $id)->update(['slug' => '__slugtmp_' . $id]);
        }

        // Pass 2 — assign clean, name-based, de-duplicated slugs.
        $used = [];
        foreach (Product::orderBy('id')->get(['id', 'name']) as $p) {
            $base = Str::slug($p->name) ?: 'product';
            $slug = $base;
            $i = 2;
            while (in_array($slug, $used, true)) {
                $slug = $base . '-' . $i++;
            }
            $used[] = $slug;
            DB::table('products')->where('id', $p->id)->update(['slug' => $slug]);
        }

        // URLs changed → refresh cached catalog reads once.
        app(CatalogCache::class)->bust();
    }

    public function down(): void
    {
        // Irreversible: the original random suffixes are not recoverable.
    }
};
