<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /** Homepage "Shop by Category" caps featured categories at this many. */
    public const HOME_FEATURED_LIMIT = 8;

    public function index(Request $request)
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $filtering = $request->filled('search') || $dr->range !== 'all';

        $base = $dr->applyTo(Category::withCount('products'));
        if ($request->filled('search')) {
            $base->where('name', 'like', "%{$request->search}%");
        }
        $all = $base->orderBy('sort_order')->orderBy('name')->get();

        if ($filtering) {
            // Flat list of matches (the parent→child tree layout doesn't apply while filtering).
            $ordered = $all->map(fn ($c) => $this->row($c))->values();
        } else {
            $ordered = collect();
            foreach ($all->whereNull('parent_id') as $parent) {
                $ordered->push($this->row($parent));
                foreach ($all->where('parent_id', $parent->id) as $child) {
                    $ordered->push($this->row($child));
                }
            }
            $ordered = $ordered->values();
        }

        return Inertia::render('Admin/Categories/Index', [
            'categories'         => $ordered,
            // Parent options + featured counts come from the full set, independent of filters.
            'parents'            => Category::whereNull('parent_id')->orderBy('name')->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'featuredCount'      => Category::where('is_featured', true)->count(),
            'featuredLimit'      => self::HOME_FEATURED_LIMIT,
            'canManageFeatured'  => (bool) $request->user()?->hasPermission('categories.manage_featured'),
            'filters'            => array_merge($request->only(['search']), $dr->filters()),
        ]);
    }

    private function row(Category $c): array
    {
        return [
            'id'                  => $c->id,
            'parent_id'           => $c->parent_id,
            'name'                => $c->name,
            'slug'                => $c->slug,
            'description'         => $c->description,
            'icon'                => $c->icon,
            'image'               => $c->image,
            'image_url'           => $c->image ? Storage::url($c->image) : null,
            'is_active'           => $c->is_active,
            'sort_order'          => $c->sort_order,
            'is_featured'         => $c->is_featured,
            'featured_sort_order' => $c->featured_sort_order,
            'products_count'      => $c->products_count,
        ];
    }

    public function store(Request $request)
    {
        $data = $this->applyImage($request, $this->applyFeatured($request, $this->validateData($request)));

        Category::create($data);
        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->applyImage($request, $this->applyFeatured($request, $this->validateData($request, $category)));

        // A category cannot be its own parent.
        if ((int) ($data['parent_id'] ?? 0) === $category->id) {
            $data['parent_id'] = null;
        }

        $category->update($data);
        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Cannot delete a category with products.');
        }
        // Image is a shared Media Library asset — don't delete the underlying file.
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:categories,id',
        ]);

        $deleted = 0;
        $skipped = 0;
        foreach (Category::withCount('products')->whereIn('id', $data['ids'])->get() as $category) {
            if ($category->products_count > 0) {
                $skipped++;
                continue;
            }
            $category->delete();
            $deleted++;
        }

        $msg = "{$deleted} category(ies) deleted." . ($skipped ? " {$skipped} skipped (still have products)." : '');
        return back()->with($deleted ? 'success' : 'error', $msg);
    }

    private function validateData(Request $request, ?Category $category = null): array
    {
        $slugRule = 'required|string|max:100|unique:categories,slug' . ($category ? ',' . $category->id : '');

        return $request->validate([
            'parent_id'   => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:100',
            'slug'        => $slugRule,
            'description' => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:20',
            'sort_order'  => 'integer|min:0',
            'is_active'   => 'boolean',
        ]);
    }

    /** Category image is chosen from the Media Library (a stored path), not uploaded here. */
    private function applyImage(Request $request, array $data): array
    {
        $request->validate([
            'image_path'   => 'nullable|string|max:255',
            'remove_image' => 'boolean',
        ]);

        if ($request->filled('image_path')) {
            $data['image'] = $request->image_path;
        } elseif ($request->boolean('remove_image')) {
            $data['image'] = null;
        }

        return $data;
    }

    /**
     * Only users with "Manage Featured Categories" may set the featured flag/order.
     * Without the permission these fields are ignored entirely (left unchanged).
     */
    private function applyFeatured(Request $request, array $data): array
    {
        if (! $request->user()?->hasPermission('categories.manage_featured')) {
            return $data;
        }

        $extra = $request->validate([
            'is_featured'         => 'boolean',
            'featured_sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_featured'] = (bool) ($extra['is_featured'] ?? false);
        $data['featured_sort_order'] = (int) ($extra['featured_sort_order'] ?? 0);

        return $data;
    }
}
