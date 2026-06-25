<?php

namespace App\Http\Middleware;

use App\Support\PageContent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Storefront maintenance mode. When enabled in Settings, public visitors get a
 * styled 503 page while admins keep full access (panel + live site) so the
 * store can be updated safely. Toggle: Admin → Settings → Maintenance mode.
 */
class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(PageContent::class)->get('settings');

        if (empty($settings['maintenance_mode'])) {
            return $next($request);
        }

        // Admins manage the store — never lock them out (panel + live preview).
        if (Auth::check() && Auth::user()->isAdmin()) {
            return $next($request);
        }

        // Keep the admin panel, the sign-in flow, health check, service worker
        // and server-to-server payment callbacks reachable during maintenance.
        if ($request->is('admin', 'admin/*', 'sw.js', 'up', 'storage/*')) {
            return $next($request);
        }
        $name = optional($request->route())->getName() ?? '';
        $allowed = [
            'login', 'login.store', 'logout',
            'password.request', 'password.email', 'password.reset', 'password.store',
            'invite.accept', 'invite.complete',
        ];
        if (in_array($name, $allowed, true) || str_starts_with($name, 'payment.')) {
            return $next($request);
        }

        // Inertia XHR navigations can't render a standalone page — force a hard
        // reload so the browser requests the maintenance view as a full page.
        if ($request->header('X-Inertia')) {
            return response('', 409)->header('X-Inertia-Location', $request->fullUrl());
        }

        return response()
            ->view('maintenance', ['s' => $settings], 503)
            ->header('Retry-After', '3600');
    }
}
