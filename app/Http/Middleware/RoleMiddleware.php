<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user()) {
            return redirect('/login');
        }

        try {
            $userRole = $request->user()->role->slug ?? null;
        } catch (\Exception $e) {
            $userRole = null;
        }

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}