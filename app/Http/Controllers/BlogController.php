<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /** Blog listing — optionally scoped to a category or tag. */
    public function index(Request $request, ?BlogCategory $category = null, ?BlogTag $tag = null): Response
    {
        $query = BlogPost::published()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->latest('published_at')->latest('id');

        if ($category) {
            $query->where('blog_category_id', $category->id);
        }
        if ($tag) {
            $query->whereHas('tags', fn ($q) => $q->where('blog_tags.id', $tag->id));
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('title', 'like', "%{$s}%")->orWhere('excerpt', 'like', "%{$s}%"));
        }

        $posts = $query->paginate(9)->withQueryString()->through(fn ($p) => $this->card($p));

        // Featured post banner — only on the unfiltered listing's first page.
        $featured = null;
        if (! $category && ! $tag && ! $request->filled('search') && $request->integer('page', 1) === 1) {
            $post = BlogPost::published()->where('is_featured', true)
                ->with(['category:id,name,slug', 'author:id,name'])
                ->latest('published_at')->first();
            $featured = $post ? $this->card($post) : null;
        }

        return Inertia::render('Blog/Index', [
            'posts'      => $posts,
            'featured'   => $featured,
            'categories' => $this->sidebarCategories(),
            'tags'       => $this->sidebarTags(),
            'context'    => [
                'type'  => $category ? 'category' : ($tag ? 'tag' : 'all'),
                'name'  => $category?->name ?? $tag?->name,
                'slug'  => $category?->slug ?? $tag?->slug,
            ],
            'filters'    => $request->only('search'),
        ]);
    }

    public function category(Request $request, BlogCategory $category): Response
    {
        abort_unless($category->is_active, 404);
        return $this->index($request, $category, null);
    }

    public function tag(Request $request, BlogTag $tag): Response
    {
        return $this->index($request, null, $tag);
    }

    public function show(BlogPost $post): Response
    {
        abort_unless(
            $post->status === 'published' && (! $post->published_at || $post->published_at->isPast()),
            404
        );

        $post->load(['category:id,name,slug', 'author:id,name', 'tags:id,name,slug']);

        // Related posts — same category first, then topped up with other recent posts
        // (excluding this one and any already included), for the slider below.
        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($q) => $q->where('blog_category_id', $post->blog_category_id))
            ->with('category:id,name,slug')
            ->latest('published_at')->take(12)->get();

        if ($related->count() < 12) {
            $more = BlogPost::published()
                ->where('id', '!=', $post->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->with('category:id,name,slug')
                ->latest('published_at')->take(12 - $related->count())->get();
            $related = $related->concat($more);
        }

        return Inertia::render('Blog/Show', [
            'post' => [
                'id'               => $post->id,
                'title'            => $post->title,
                'slug'             => $post->slug,
                'excerpt'          => $post->excerpt,
                'content'          => $post->content,
                'featured_image_url' => $post->featured_image_url,
                'content_images'   => collect($post->content_images ?? [])->map(fn ($p) => BlogPost::imageUrl($p))->all(),
                'meta_title'       => $post->meta_title,
                'meta_description' => $post->meta_description,
                'meta_keywords'    => $post->meta_keywords,
                'og_image_url'     => $post->og_image_url,
                'reading_time'     => $post->estimatedReadingTime(),
                'author'           => $post->author_name ?: $post->author?->name,
                'published_at'     => $post->published_at?->format('d M Y'),
                'category'         => $post->category ? ['name' => $post->category->name, 'slug' => $post->category->slug] : null,
                'tags'             => $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
            ],
            'related'    => $related->map(fn ($p) => $this->card($p)),
            'categories' => $this->sidebarCategories(),
            'tags'       => $this->sidebarTags(),
        ]);
    }

    private function card(BlogPost $p): array
    {
        return [
            'id'                 => $p->id,
            'title'              => $p->title,
            'slug'               => $p->slug,
            'excerpt'            => $p->excerpt,
            'featured_image_url' => $p->featured_image_url,
            'reading_time'       => $p->estimatedReadingTime(),
            'author'             => $p->author_name ?: $p->author?->name,
            'published_at'       => $p->published_at?->format('d M Y'),
            'category'           => $p->category ? ['name' => $p->category->name, 'slug' => $p->category->slug] : null,
        ];
    }

    private function sidebarCategories()
    {
        return BlogCategory::where('is_active', true)
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug, 'count' => $c->posts_count]);
    }

    private function sidebarTags()
    {
        return BlogTag::whereHas('posts', fn ($q) => $q->published())
            ->orderBy('name')->take(30)->get(['id', 'name', 'slug'])
            ->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]);
    }
}
