<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\Recaptcha\RecaptchaService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly RecaptchaService $recaptcha) {}

    public function store(Request $request, string $slug)
    {
        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5|max:1000',
        ]);

        if ($msg = $this->recaptcha->verifyRequest($request, 'review')) {
            return back()->withErrors(['review' => $msg]);
        }

        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // One review per user per product
        if (Review::where('product_id', $product->id)->where('user_id', auth()->id())->exists()) {
            return back()->withErrors(['review' => 'You have already reviewed this product.']);
        }

        Review::create([
            'product_id'  => $product->id,
            'user_id'     => auth()->id(),
            'rating'      => $request->rating,
            'comment'     => $request->comment,
            'is_approved' => true,
        ]);

        return back()->with('success', 'Thank you! Your review has been submitted.');
    }
}
