<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /** Active categories ordered as parents → indented children for <select>. */
    private function categoriesForSelect()
    {
        $cats = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'parent_id']);
        $ordered = collect();
        foreach ($cats->whereNull('parent_id') as $parent) {
            $ordered->push(['id' => $parent->id, 'name' => $parent->name]);
            foreach ($cats->where('parent_id', $parent->id) as $child) {
                $ordered->push(['id' => $child->id, 'name' => '— ' . $child->name]);
            }
        }
        return $ordered->values();
    }

    /** Build a URL-safe, unique product slug (optionally ignoring one product's own row). */
    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'product';
        $original = $slug;
        $i = 2;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    public function index(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(Product::with('category'));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $products = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn($p) => [
                'id'                  => $p->id,
                'name'                => $p->name,
                'slug'                => $p->slug,
                'sku'                 => $p->sku,
                'category_id'         => $p->category_id,
                'tags'                => $p->tags ?? [],
                'short_description'   => $p->short_description,
                'description'         => $p->description,
                'usps'                => $p->usps ?? [],
                'has_variations'      => $p->has_variations,
                'variations'          => $p->variations ?? [],
                'price'               => $p->price,
                'discount_price'      => $p->discount_price,
                'sale_starts_at'      => $p->sale_starts_at?->format('Y-m-d'),
                'sale_ends_at'        => $p->sale_ends_at?->format('Y-m-d'),
                'meta_title'          => $p->meta_title,
                'meta_description'    => $p->meta_description,
                'stock'               => $p->stock,
                'is_active'           => $p->is_active,
                'is_featured'         => $p->is_featured,
                'show_on_hero'        => $p->show_on_hero,
                'featured_image_url'  => $p->featured_image_url,
                'category'            => ['name' => $p->category->name],
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products'   => $products,
            'categories' => $this->categoriesForSelect(),
            'filters'    => array_merge($request->only(['search', 'category']), $dr->filters()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'categories' => $this->categoriesForSelect(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'       => 'required|exists:categories,id',
            'name'              => 'required|string|max:255',
            'slug'              => 'nullable|string|max:255|alpha_dash|unique:products,slug',
            'sku'               => 'nullable|string|max:100|unique:products,sku',
            'tags'              => 'nullable|array',
            'tags.*'            => 'string|max:50',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string|max:500',
            'short_description' => 'nullable|string|max:500',
            'description'       => 'nullable|string',
            'usps'              => 'nullable|array',
            'usps.*.title'      => 'required_with:usps|string|max:120',
            'usps.*.text'       => 'nullable|string|max:255',
            'has_variations'    => 'boolean',
            'variations'        => 'nullable|array',
            'variations.*.name'         => 'required|string|max:60',
            'variations.*.type'         => 'nullable|string|max:20',
            'variations.*.options'      => 'nullable|array',
            'variations.*.options.*.value' => 'nullable|string|max:120',
            'variations.*.options.*.image' => 'nullable|string|max:1000',
            'variations.*.options.*.price' => 'nullable|numeric',
            'variations.*.options.*.stock' => 'nullable|integer|min:0',
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|min:0|lt:price',
            'sale_starts_at'    => 'nullable|date',
            'sale_ends_at'      => 'nullable|date|after_or_equal:sale_starts_at',
            'stock'             => 'required|integer|min:0',
            'is_featured'       => 'boolean',
            'show_on_hero'      => 'boolean',
            'is_active'           => 'boolean',
            'featured_image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'featured_image_path' => 'nullable|string|max:255', // selected from media library
            'gallery_images.*'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery_paths'       => 'nullable|array',           // selected from media library
            'gallery_paths.*'     => 'string|max:255',
        ]);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('products', 'public');
        } elseif ($request->filled('featured_image_path')) {
            $data['featured_image'] = $request->featured_image_path;
        }

        $data['slug'] = $this->uniqueSlug($data['slug'] ?? '' ?: $request->name);
        unset($data['gallery_images'], $data['gallery_paths'], $data['featured_image_path']);

        $product = Product::create($data);

        $this->saveGalleryImages($product, $request, 0);

        return redirect()->route('admin.products.index')->with('success', 'Product created!');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => [
                'id'                  => $product->id,
                'name'                => $product->name,
                'slug'                => $product->slug,
                'sku'                 => $product->sku,
                'category_id'         => $product->category_id,
                'tags'                => $product->tags ?? [],
                'short_description'   => $product->short_description,
                'description'         => $product->description,
                'usps'                => $product->usps ?? [],
                'has_variations'      => $product->has_variations,
                'variations'          => $product->variations ?? [],
                'price'               => $product->price,
                'discount_price'      => $product->discount_price,
                'sale_starts_at'      => $product->sale_starts_at?->format('Y-m-d'),
                'sale_ends_at'        => $product->sale_ends_at?->format('Y-m-d'),
                'meta_title'          => $product->meta_title,
                'meta_description'    => $product->meta_description,
                'stock'               => $product->stock,
                'is_featured'         => $product->is_featured,
                'show_on_hero'        => $product->show_on_hero,
                'is_active'           => $product->is_active,
                'featured_image_url'  => $product->featured_image_url,
                'gallery_images'      => $product->images->map(fn($i) => [
                    'id'         => $i->id,
                    'image_url'  => $i->image_url,
                    'sort_order' => $i->sort_order,
                ]),
            ],
            'categories' => $this->categoriesForSelect(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id'           => 'required|exists:categories,id',
            'name'                  => 'required|string|max:255',
            'slug'                  => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product->id)],
            'sku'                   => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'tags'                  => 'nullable|array',
            'tags.*'                => 'string|max:50',
            'meta_title'            => 'nullable|string|max:255',
            'meta_description'      => 'nullable|string|max:500',
            'short_description'     => 'nullable|string|max:500',
            'description'           => 'nullable|string',
            'usps'                  => 'nullable|array',
            'usps.*.title'          => 'required_with:usps|string|max:120',
            'usps.*.text'           => 'nullable|string|max:255',
            'has_variations'        => 'boolean',
            'variations'            => 'nullable|array',
            'variations.*.name'            => 'required|string|max:60',
            'variations.*.type'            => 'nullable|string|max:20',
            'variations.*.options'         => 'nullable|array',
            'variations.*.options.*.value' => 'nullable|string|max:120',
            'variations.*.options.*.image' => 'nullable|string|max:1000',
            'variations.*.options.*.price' => 'nullable|numeric',
            'variations.*.options.*.stock' => 'nullable|integer|min:0',
            'price'                 => 'required|numeric|min:0',
            'discount_price'        => 'nullable|numeric|min:0|lt:price',
            'sale_starts_at'        => 'nullable|date',
            'sale_ends_at'          => 'nullable|date|after_or_equal:sale_starts_at',
            'stock'                 => 'required|integer|min:0',
            'is_featured'           => 'boolean',
            'show_on_hero'          => 'boolean',
            'is_active'             => 'boolean',
            'featured_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'featured_image_path'   => 'nullable|string|max:255',
            'gallery_images.*'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery_paths'         => 'nullable|array',
            'gallery_paths.*'       => 'string|max:255',
            'delete_gallery_ids'    => 'nullable|array',
            'delete_gallery_ids.*'  => 'integer',
            'gallery_order'         => 'nullable|array',
            'gallery_order.*'       => 'integer',
        ]);

        if ($request->hasFile('featured_image')) {
            $this->deleteIfLegacy($product->featured_image);
            $data['featured_image'] = $request->file('featured_image')->store('products', 'public');
        } elseif ($request->filled('featured_image_path')) {
            $this->deleteIfLegacy($product->featured_image);
            $data['featured_image'] = $request->featured_image_path;
        }

        // Remove gallery images marked for deletion
        if (! empty($data['delete_gallery_ids'])) {
            $toDelete = ProductImage::whereIn('id', $data['delete_gallery_ids'])
                ->where('product_id', $product->id)
                ->get();
            foreach ($toDelete as $img) {
                $this->deleteIfLegacy($img->image);
                $img->delete();
            }
        }
        // Persist the drag & drop sequence of the saved gallery images
        if (! empty($data['gallery_order'])) {
            foreach (array_values($data['gallery_order']) as $i => $imageId) {
                ProductImage::where('product_id', $product->id)
                    ->where('id', $imageId)
                    ->update(['sort_order' => $i]);
            }
        }
        // Keep the slug editable & unique, but never rewrite it on an unrelated edit:
        //  - field not submitted        → keep the existing slug
        //  - submitted, same as current → keep it verbatim (preserves legacy casing)
        //  - submitted blank            → regenerate from the name
        //  - submitted & changed        → normalize + de-duplicate
        $submitted = $request->has('slug') ? trim((string) $request->input('slug')) : null;
        if ($submitted === null || $submitted === $product->slug) {
            $data['slug'] = $product->slug;
        } else {
            $data['slug'] = $this->uniqueSlug($submitted !== '' ? $submitted : $request->name, $product->id);
        }
        unset($data['gallery_images'], $data['gallery_paths'], $data['delete_gallery_ids'], $data['gallery_order'], $data['featured_image_path']);

        $product->update($data);

        $lastOrder = $product->images()->max('sort_order') ?? -1;
        $this->saveGalleryImages($product, $request, $lastOrder + 1);

        return redirect()->route('admin.products.index')->with('success', 'Product updated!');
    }

    /** Save gallery from uploaded files AND media-library paths */
    private function saveGalleryImages(Product $product, Request $request, int $startOrder): void
    {
        $order = $startOrder;

        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $file) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image'      => $file->store('products/gallery', 'public'),
                    'sort_order' => $order++,
                ]);
            }
        }

        foreach ((array) $request->input('gallery_paths', []) as $path) {
            if (! $path) continue;
            ProductImage::create([
                'product_id' => $product->id,
                'image'      => $path,
                'sort_order' => $order++,
            ]);
        }
    }

    /** Only delete files that were uploaded directly to the product (legacy),
     *  never media-library files (they are shared and managed separately). */
    private function deleteIfLegacy(?string $path): void
    {
        if ($path && str_starts_with($path, 'products/')) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Returns gallery images as JSON — used by the inline edit modal */
    public function galleryData(Product $product): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $product->images->map(fn($i) => [
                'id'         => $i->id,
                'image_url'  => $i->image_url,
                'sort_order' => $i->sort_order,
            ])
        );
    }

    public function destroy(Product $product)
    {
        $this->removeProduct($product);
        return back()->with('success', 'Product deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        $count = 0;
        foreach (Product::with('images')->whereIn('id', $data['ids'])->get() as $product) {
            $this->removeProduct($product);
            $count++;
        }

        return back()->with('success', "{$count} product(s) deleted.");
    }

    /** Delete a product, removing only legacy direct-uploaded files (media-library files stay). */
    private function removeProduct(Product $product): void
    {
        $this->deleteIfLegacy($product->featured_image);
        foreach ($product->images as $img) {
            $this->deleteIfLegacy($img->image);
        }
        $product->delete();
    }

    /** Duplicate a product (created inactive, without SKU) and jump to its edit page. */
    public function duplicate(Product $product)
    {
        $copy = $product->replicate(['sku']);
        $copy->name         = $product->name . ' (Copy)';
        $copy->slug         = $this->uniqueSlug($copy->name);
        $copy->sku          = null;
        $copy->is_active    = false;
        $copy->show_on_hero = false;
        $copy->featured_image = $this->copyIfLegacy($product->featured_image);
        $copy->save();

        foreach ($product->images as $img) {
            ProductImage::create([
                'product_id' => $copy->id,
                'image'      => $this->copyIfLegacy($img->image),
                'sort_order' => $img->sort_order,
            ]);
        }

        return redirect()->route('admin.products.edit', $copy->id)
            ->with('success', 'Product duplicated — review and activate it when ready.');
    }

    /** Legacy product files get a physical copy so deleting one product never breaks the other. */
    private function copyIfLegacy(?string $path): ?string
    {
        if ($path && str_starts_with($path, 'products/') && Storage::disk('public')->exists($path)) {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            $new = 'products/' . Str::random(24) . ($ext ? ".{$ext}" : '');
            Storage::disk('public')->copy($path, $new);
            return $new;
        }
        return $path;
    }

    /** Bulk actions from the products list (beyond delete). */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'ids'         => 'required|array|min:1',
            'ids.*'       => 'integer|exists:products,id',
            'action'      => 'required|in:activate,deactivate,feature,unfeature,set_category',
            'category_id' => 'required_if:action,set_category|nullable|exists:categories,id',
        ]);

        $values = match ($data['action']) {
            'activate'     => ['is_active' => true],
            'deactivate'   => ['is_active' => false],
            'feature'      => ['is_featured' => true],
            'unfeature'    => ['is_featured' => false],
            'set_category' => ['category_id' => $data['category_id']],
        };

        $count = Product::whereIn('id', $data['ids'])->update($values);
        app(\App\Support\CatalogCache::class)->bust(); // mass update skips model events

        return back()->with('success', "{$count} product(s) updated.");
    }

    /** CSV export of the catalog (same columns the importer understands). */
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filename = 'products-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['sku', 'name', 'category', 'price', 'discount_price', 'sale_starts_at', 'sale_ends_at', 'stock', 'is_active', 'is_featured', 'tags', 'short_description']);
            Product::with('category:id,name')->orderBy('id')->chunk(200, function ($products) use ($out) {
                foreach ($products as $p) {
                    fputcsv($out, [
                        $p->sku,
                        $p->name,
                        $p->category?->name,
                        $p->price,
                        $p->discount_price,
                        $p->sale_starts_at?->format('Y-m-d'),
                        $p->sale_ends_at?->format('Y-m-d'),
                        $p->stock,
                        $p->is_active ? 1 : 0,
                        $p->is_featured ? 1 : 0,
                        implode('|', $p->tags ?? []),
                        $p->short_description,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** CSV import — rows are matched by SKU (update) or created. dry_run previews without saving. */
    public function import(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file'    => 'required|file|mimes:csv,txt|max:5120',
            'dry_run' => 'nullable|boolean',
        ]);

        $dryRun = $request->boolean('dry_run');
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), fgetcsv($handle) ?: []);

        if (! in_array('sku', $header) && ! in_array('name', $header)) {
            fclose($handle);
            return response()->json(['message' => 'CSV must have a header row with at least a "sku" or "name" column.'], 422);
        }

        $categories = Category::pluck('id', 'name'); // name → id
        $updated = 0; $created = 0; $errors = [];
        $rowNum = 1;

        $apply = function () use ($handle, $header, $categories, $dryRun, &$updated, &$created, &$errors, &$rowNum, $request) {
            while (($raw = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (count(array_filter($raw, fn ($c) => trim((string) $c) !== '')) === 0) continue;
                $row = [];
                foreach ($header as $i => $key) $row[$key] = isset($raw[$i]) ? trim((string) $raw[$i]) : '';

                $product = ! empty($row['sku']) ? Product::where('sku', $row['sku'])->first() : null;

                $attrs = [];
                if (isset($row['name']) && $row['name'] !== '')            $attrs['name'] = $row['name'];
                if (isset($row['price']) && $row['price'] !== '')          $attrs['price'] = (float) $row['price'];
                if (isset($row['discount_price']))                         $attrs['discount_price'] = $row['discount_price'] === '' ? null : (float) $row['discount_price'];
                if (isset($row['sale_starts_at']))                         $attrs['sale_starts_at'] = $row['sale_starts_at'] === '' ? null : $row['sale_starts_at'];
                if (isset($row['sale_ends_at']))                           $attrs['sale_ends_at'] = $row['sale_ends_at'] === '' ? null : $row['sale_ends_at'];
                if (isset($row['stock']) && $row['stock'] !== '')          $attrs['stock'] = max(0, (int) $row['stock']);
                if (isset($row['is_active']) && $row['is_active'] !== '')  $attrs['is_active'] = (bool) (int) $row['is_active'];
                if (isset($row['is_featured']) && $row['is_featured'] !== '') $attrs['is_featured'] = (bool) (int) $row['is_featured'];
                if (isset($row['tags']) && $row['tags'] !== '')            $attrs['tags'] = array_values(array_filter(array_map('trim', preg_split('/[|,]/', $row['tags']))));
                if (isset($row['short_description']) && $row['short_description'] !== '') $attrs['short_description'] = $row['short_description'];

                if (! empty($row['category'])) {
                    $catId = $categories[$row['category']] ?? null;
                    if (! $catId) { $errors[] = "Row {$rowNum}: unknown category \"{$row['category']}\"."; continue; }
                    $attrs['category_id'] = $catId;
                }

                if (isset($attrs['discount_price'], $attrs['price']) && $attrs['discount_price'] !== null && $attrs['discount_price'] >= $attrs['price']) {
                    $errors[] = "Row {$rowNum}: discount_price must be lower than price.";
                    continue;
                }

                if ($product) {
                    if (! $dryRun) {
                        $prevStock = (int) $product->stock;
                        $product->update($attrs);
                        if (isset($attrs['stock']) && $attrs['stock'] !== $prevStock) {
                            \App\Models\InventoryLog::create([
                                'product_id'     => $product->id,
                                'user_id'        => $request->user()->id,
                                'user_name'      => $request->user()->name,
                                'previous_stock' => $prevStock,
                                'added_quantity' => $attrs['stock'] - $prevStock,
                                'new_stock'      => $attrs['stock'],
                                'note'           => 'CSV import',
                                'created_at'     => now(),
                            ]);
                        }
                    }
                    $updated++;
                } else {
                    if (empty($attrs['name']) || ! isset($attrs['price']) || empty($attrs['category_id'])) {
                        $errors[] = "Row {$rowNum}: new products need name, category and price" . (! empty($row['sku']) ? " (SKU \"{$row['sku']}\" not found)" : '') . '.';
                        continue;
                    }
                    if (! $dryRun) {
                        $attrs['slug']  = $this->uniqueSlug($attrs['name']);
                        $attrs['sku']   = $row['sku'] ?: null;
                        $attrs['stock'] = $attrs['stock'] ?? 0;
                        Product::create($attrs);
                    }
                    $created++;
                }
            }
        };

        if ($dryRun) {
            $apply();
        } else {
            \Illuminate\Support\Facades\DB::transaction($apply);
        }
        fclose($handle);

        return response()->json([
            'dry_run' => $dryRun,
            'updated' => $updated,
            'created' => $created,
            'errors'  => array_slice($errors, 0, 30),
            'error_count' => count($errors),
        ]);
    }
}
