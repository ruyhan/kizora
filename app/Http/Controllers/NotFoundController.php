<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * The universal 404 page. Used by the catch-all fallback route (unknown URLs)
 * and by the exception handler (abort(404), missing products/orders, etc.).
 */
class NotFoundController extends Controller
{
    /** File-like paths (missing images, scripts…) get a bare 404 instead of a full page. */
    private const ASSET_PATTERN = '/\.(js|mjs|css|map|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|eot|json|txt|xml|webmanifest|mp4|webm|pdf)$/i';

    public function __invoke(Request $request): Response
    {
        return self::render($request);
    }

    public static function render(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        if (preg_match(self::ASSET_PATTERN, $request->path())) {
            return response('Not found', 404, ['Content-Type' => 'text/plain']);
        }

        // Inside /admin, staff get the page within the admin panel.
        $inAdmin = $request->is('admin', 'admin/*') && Auth::user()?->isAdmin();

        return Inertia::render('Errors/NotFound', [
            'admin' => (bool) $inAdmin,
            'path'  => '/' . ltrim($request->path(), '/'),
        ])->toResponse($request)->setStatusCode(404);
    }
}
