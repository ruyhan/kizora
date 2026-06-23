<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->expectsJson()) {
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                return response()->json(['message' => 'Unauthorized.'], 403);
            }
        }

        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access the admin panel.');
        }

        if (!Auth::user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'You do not have admin access.');
        }

        return $next($request);
    }
}
