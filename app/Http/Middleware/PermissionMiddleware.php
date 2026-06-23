<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Granular permission gate. Usage: ->middleware('permission:products.view')
 * or ->middleware('permission:products.edit,products.create') (any-of).
 * Super Admin bypasses. Denials are written to the audit log.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyPermission($permissions)) {
            AuditLog::record('denied', 'Security',
                'Blocked from ' . $request->path() . ' (requires: ' . implode(' | ', $permissions) . ')');

            if ($request->expectsJson()) {
                return response()->json(['message' => 'This action is unauthorized.'], 403);
            }

            return Inertia::render('Errors/AccessDenied', [
                'required' => $permissions,
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
