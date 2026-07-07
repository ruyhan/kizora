<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Review;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        // Opening the manager clears the "new reviews" sidebar badge.
        Review::whereNull('seen_at')->update(['seen_at' => now()]);

        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(Review::with(['product:id,name,slug', 'user:id,name,email']))->latest();

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }
        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%"))
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
                ->orWhere('comment', 'like', "%{$s}%"));
        }

        $reviews = $query->paginate(20)->withQueryString()->through(fn ($r) => [
            'id'          => $r->id,
            'product'     => $r->product?->name ?? '— (deleted)',
            'product_slug' => $r->product?->slug,
            'reviewer'    => $r->user?->name ?? 'Guest',
            'email'       => $r->user?->email,
            'rating'      => $r->rating,
            'comment'     => $r->comment,
            'is_approved' => $r->is_approved,
            'admin_reply' => $r->admin_reply,
            'created_at'  => $r->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $reviews,
            'filters' => array_merge($request->only(['status', 'rating', 'search']), $dr->filters()),
            'stats'   => [
                'total'    => Review::count(),
                'approved' => Review::where('is_approved', true)->count(),
                'pending'  => Review::where('is_approved', false)->count(),
                'avg'      => round((float) Review::avg('rating'), 1),
            ],
        ]);
    }

    public function update(Request $request, Review $review)
    {
        $data = $request->validate([
            'rating'      => 'required|integer|min:1|max:5',
            'comment'     => 'required|string|min:2|max:2000',
            'is_approved' => 'boolean',
        ]);
        $review->update($data);
        AuditLog::record('updated', 'Reviews', "Edited review #{$review->id}");

        return back()->with('success', 'Review updated.');
    }

    /** Public shop-owner reply shown under the review on the product page. */
    public function reply(Request $request, Review $review)
    {
        $data = $request->validate(['admin_reply' => 'nullable|string|max:1000']);

        $review->update([
            'admin_reply'    => $data['admin_reply'] ?: null,
            'admin_reply_at' => $data['admin_reply'] ? now() : null,
        ]);
        AuditLog::record('updated', 'Reviews', ($data['admin_reply'] ? 'Replied to' : 'Removed reply from') . " review #{$review->id}");

        return back()->with('success', $data['admin_reply'] ? 'Reply published.' : 'Reply removed.');
    }

    public function toggle(Review $review)
    {
        $review->update(['is_approved' => ! $review->is_approved]);
        AuditLog::record('updated', 'Reviews', "Review #{$review->id} " . ($review->is_approved ? 'approved' : 'hidden'));

        return back()->with('success', $review->is_approved ? 'Review approved & published.' : 'Review hidden from the storefront.');
    }

    public function destroy(Review $review)
    {
        $review->delete();
        AuditLog::record('deleted', 'Reviews', "Deleted review #{$review->id}");

        return back()->with('success', 'Review deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);
        Review::whereIn('id', $data['ids'])->delete();
        AuditLog::record('deleted', 'Reviews', count($data['ids']) . ' reviews deleted');

        return back()->with('success', count($data['ids']) . ' review(s) deleted.');
    }
}
