<?php

namespace App\Http\Middleware;

use App\Services\Permissions\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level permission gate.
 *
 * Variadic on permission slugs (`permission:patients.create` or
 * `permission:patients.create,patients.edit`). OR'd — the request
 * passes if the user holds at least one of the listed permissions.
 *
 * Backed by PermissionService, which honours the multi-role pivot
 * (User::allRoleSlugs) and the wildcard semantics from
 * HospitalPermissions::ROLE_PERMISSIONS.
 */
class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect('/login');
        }

        // Pass the request-bound user explicitly. The service falls
        // back to Auth::user() if none is given, but in middleware
        // contexts Auth::user() may not be hydrated yet (or may not
        // match the request's user resolver), so passing the request's
        // user is the reliable path.
        if (!PermissionService::allowsAny($permissions, $user)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
