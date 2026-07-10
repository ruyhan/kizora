<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function index(Request $request): Response
    {
        $query = BlogPost::with(['category:id,name', 'author:id,name']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('title', 'like', "%{$s}%")->orWhere('excerpt', 'like', "%{$s}%"));
        }
        if ($request->filled('category')) {
            $query->where('blog_category_id', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (BlogPost $p) => [
                'id'                 => $p->id,
                'title'              => $p->title,
                'slug'               => $p->slug,
                'status'             => $p->status,
                'is_featured'        => $p->is_featured,
                'featured_image_url' => $p->featured_image_url,
                'category'           => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name] : null,
                'author'             => $p->author_name ?: $p->author?->name,
                'published_at'       => $p->published_at?->format('d M Y'),
                'created_at'         => $p->created_at->format('d M Y'),
            ]);

        return Inertia::render('Admin/Blog/Index', [
            'posts'      => $posts,
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name']),
            'filters'    => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Blog/Form', [
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name']),
            'allTags'    => BlogTag::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $post = new BlogPost();
        $this->fill($post, $data, $request);
        $post->save();
        $this->syncTags($post, $data['tags'] ?? []);

        return redirect()->route('admin.blog.index')->with('success', 'Blog post created!');
    }

    public function edit(BlogPost $post): Response
    {
        $post->load('tags:id,name');

        return Inertia::render('Admin/Blog/Form', [
            'post' => [
                'id'               => $post->id,
                'blog_category_id' => $post->blog_category_id,
                'title'            => $post->title,
                'slug'             => $post->slug,
                'author_name'      => $post->author_name,
                'excerpt'          => $post->excerpt,
                'content'          => $post->content,
                'featured_image'   => $post->featured_image,
                'content_images'   => $post->content_images ?? [],
                'meta_title'       => $post->meta_title,
                'meta_description' => $post->meta_description,
                'meta_keywords'    => $post->meta_keywords,
                'og_image'         => $post->og_image,
                'status'           => $post->status,
                'is_featured'      => $post->is_featured,
                'reading_time'     => $post->reading_time,
                'published_at'     => $post->published_at?->format('Y-m-d'),
                'tags'             => $post->tags->pluck('name'),
            ],
            'categories' => BlogCategory::orderBy('name')->get(['id', 'name']),
            'allTags'    => BlogTag::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request, BlogPost $post)
    {
        $data = $this->validateData($request, $post);

        $this->fill($post, $data, $request);
        $post->save();
        $this->syncTags($post, $data['tags'] ?? []);

        return redirect()->route('admin.blog.index')->with('success', 'Blog post updated!');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();
        return back()->with('success', 'Blog post deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:blog_posts,id',
        ]);

        $count = BlogPost::whereIn('id', $data['ids'])->count();
        BlogPost::whereIn('id', $data['ids'])->get()->each->delete();

        return back()->with('success', "{$count} post(s) deleted.");
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function validateData(Request $request, ?BlogPost $post = null): array
    {
        return $request->validate([
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'title'            => 'required|string|max:255',
            'slug'             => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'author_name'      => 'nullable|string|max:120',
            'excerpt'          => 'nullable|string|max:500',
            'content'          => 'nullable|string',
            'featured_image'   => 'nullable|string|max:1000',
            'content_images'   => 'nullable|array',
            'content_images.*' => 'string|max:1000',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords'    => 'nullable|string|max:255',
            'og_image'         => 'nullable|string|max:1000',
            'status'           => ['required', Rule::in(['draft', 'published'])],
            'is_featured'      => 'boolean',
            'reading_time'     => 'nullable|integer|min:0|max:600',
            'published_at'     => 'nullable|date',
            'tags'             => 'nullable|array',
            'tags.*'           => 'string|max:60',
        ]);
    }

    private function fill(BlogPost $post, array $data, Request $request): void
    {
        $post->blog_category_id = $data['blog_category_id'] ?? null;
        $post->title            = $data['title'];
        $post->slug             = $this->uniqueSlug($data['slug'] ?? '' ?: $data['title'], $post->id);
        $post->author_name      = $data['author_name'] ?? null;
        $post->excerpt          = $data['excerpt'] ?? null;
        $post->content          = $data['content'] ?? null;
        $post->featured_image   = $data['featured_image'] ?? null;
        $post->content_images   = array_values(array_filter($data['content_images'] ?? []));
        $post->meta_title       = $data['meta_title'] ?? null;
        $post->meta_description = $data['meta_description'] ?? null;
        $post->meta_keywords    = $data['meta_keywords'] ?? null;
        $post->og_image         = $data['og_image'] ?? null;
        $post->status           = $data['status'];
        $post->is_featured      = (bool) ($data['is_featured'] ?? false);
        $post->reading_time     = ($data['reading_time'] ?? null) ?: null;
        $post->user_id          = $post->user_id ?: $request->user()?->id;

        // Publish date: use the given one, or stamp "now" the first time it goes live.
        if (! empty($data['published_at'])) {
            $post->published_at = $data['published_at'];
        } elseif ($data['status'] === 'published' && ! $post->published_at) {
            $post->published_at = now();
        }
    }

    private function syncTags(BlogPost $post, array $names): void
    {
        $ids = collect($names)
            ->map(fn ($n) => trim($n))
            ->filter()
            ->unique()
            ->map(fn ($n) => BlogTag::findOrCreateByName($n)->id)
            ->all();

        $post->tags()->sync($ids);
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'post';
        $original = $slug;
        $i = 2;
        while (BlogPost::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }
}
