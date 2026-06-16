<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LibraryAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if library access code is set in settings
        $code = \App\Models\Setting::get('library_access_code');

        // If no code is set, allow access
        if (empty($code)) {
            return $next($request);
        }

        // If code is set, check if verified
        if (!session()->get('library_verified')) {
            return redirect()->route('library.verify');
        }

        return $next($request);
    }
}