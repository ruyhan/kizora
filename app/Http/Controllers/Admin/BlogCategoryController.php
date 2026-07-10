<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BlogCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = BlogCategory::withCount('posts')
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(fn (BlogCategory $c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'description' => $c->description,
                'is_active'   => $c->is_active,
                'sort_order'  => $c->sort_order,
                'posts_count' => $c->posts_count,
            ]);

        return Inertia::render('Admin/Blog/Categories', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? '' ?: $data['name']);

        BlogCategory::create($data);
        return back()->with('success', 'Blog category created.');
    }

    public function update(Request $request, BlogCategory $category)
    {
        $data = $this->validateData($request, $category);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?? '' ?: $data['name'], $category->id);

        $category->update($data);
        return back()->with('success', 'Blog category updated.');
    }

    public function destroy(BlogCategory $category)
    {
        // Posts keep working — their category is set to null (nullOnDelete).
        $category->delete();
        return back()->with('success', 'Blog category deleted.');
    }

    private function validateData(Request $request, ?BlogCategory $category = null): array
    {
        return $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => ['nullable', 'string', 'max:120', 'alpha_dash', Rule::unique('blog_categories', 'slug')->ignore($category?->id)],
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
            'sort_order'  => 'integer|min:0',
        ]);
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'category';
        $original = $slug;
        $i = 2;
        while (BlogCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
