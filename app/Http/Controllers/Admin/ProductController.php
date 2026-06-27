<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
                'category_id'         => $p->category_id,
                'short_description'   => $p->short_description,
                'description'         => $p->description,
                'usps'                => $p->usps ?? [],
                'has_variations'      => $p->has_variations,
                'variations'          => $p->variations ?? [],
                'price'               => $p->price,
                'discount_price'      => $p->discount_price,
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
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|min:0|lt:price',
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

        $data['slug'] = Str::slug($request->name) . '-' . Str::random(4);
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
                'category_id'         => $product->category_id,
                'short_description'   => $product->short_description,
                'description'         => $product->description,
                'usps'                => $product->usps ?? [],
                'has_variations'      => $product->has_variations,
                'variations'          => $product->variations ?? [],
                'price'               => $product->price,
                'discount_price'      => $product->discount_price,
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
            'price'                 => 'required|numeric|min:0',
            'discount_price'        => 'nullable|numeric|min:0',
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
        unset($data['gallery_images'], $data['gallery_paths'], $data['delete_gallery_ids'], $data['featured_image_path']);

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
}
