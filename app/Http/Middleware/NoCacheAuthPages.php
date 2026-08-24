<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strip any browser / proxy caching from auth-gated HTML pages.
 *
 * Symptom: admin adds a new role or permission, but the user's
 * sidebar still shows the old menu until they hard-reload. The
 * layout reads auth()->user()->role on every request, so Laravel's
 * view is correct — but the browser is happily serving the prior
 * 200 OK from disk cache.
 *
 * Forcing `Cache-Control: no-store` on every auth-gated response
 * guarantees that the next request after a role/permission change
 * re-evaluates the layout server-side.
 *
 * JSON / API routes are skipped (they set their own headers; the
 * XHR POST in carry-over search etc. must keep cache semantics).
 */
class NoCacheAuthPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only target HTML page responses. Skip JSON / file downloads /
        // redirects (login/logout). XHR POSTs get a 302 or JSON and
        // we don't want to mess with those.
        if ($request->ajax() || $request->wantsJson()) {
            return $response;
        }
        if ($request->is('api/*') || $request->is('livewire/*')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
