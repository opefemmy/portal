<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware for Authentication
 * Protects against brute force attacks
 */
class RateLimitMiddleware
{
    /**
     * Maximum number of login attempts allowed
     */
    protected int $maxAttempts = 5;

    /**
     * Number of minutes to lock out after max attempts
     */
    protected int $lockoutMinutes = 15;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apply rate limiting only to login routes
        if ($this->isLoginRoute($request)) {
            return $this->handleRateLimit($request, $next);
        }

        return $next($request);
    }

    /**
     * Check if the request is a login route
     */
    protected function isLoginRoute(Request $request): bool
    {
        return in_array($request->route()?->getName(), [
            'login',
            'password.forgot',
            'password.verify-email',
            'password.verify-secret',
            'password.reset-form',
        ]);
    }

    /**
     * Handle rate limiting for login attempts
     */
    protected function handleRateLimit(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $email = $request->input('email', '');
        $key = $this->resolveRequestKey($ip, $email);

        // Get attempts from session or cache
        $attempts = $this->getAttempts($key);
        $lockedUntil = $this->getLockedUntil($key);

        // Check if account is locked
        if ($lockedUntil && now()->lessThan($lockedUntil)) {
            $seconds = now()->diffInSeconds($lockedUntil);

            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
                'locked' => true,
                'retry_after' => $seconds
            ], 429);
        }

        $response = $next($request);

        // If login failed, increment attempts
        if ($response->getStatusCode() === 401 || $response->getStatusCode() === 422) {
            $this->incrementAttempts($key);

            $attempts = $this->getAttempts($key);

            // Check if we should lock the account
            if ($attempts >= $this->maxAttempts) {
                $this->lockAccount($key);

                return response()->json([
                    'message' => 'Too many login attempts. Your account is locked for ' . $this->lockoutMinutes . ' minutes.',
                    'locked' => true,
                    'retry_after' => $this->lockoutMinutes * 60
                ], 429);
            }

            // Add remaining attempts to response headers
            $remainingAttempts = $this->maxAttempts - $attempts;
            $response->headers->set('X-RateLimit-Remaining', $remainingAttempts);
            $response->headers->set('X-RateLimit-Limit', $this->maxAttempts);
        } else {
            // Login successful - reset attempts
            $this->resetAttempts($key);
        }

        return $response;
    }

    /**
     * Resolve the request key for rate limiting
     */
    protected function resolveRequestKey(string $ip, string $email): string
    {
        return 'rate_limit:' . ($email ? md5($email) : $ip);
    }

    /**
     * Get current number of attempts
     */
    protected function getAttempts(string $key): int
    {
        return cache()->get($key . ':attempts', 0);
    }

    /**
     * Increment attempts counter
     */
    protected function incrementAttempts(string $key): void
    {
        $attempts = $this->getAttempts($key) + 1;
        cache()->put($key . ':attempts', $attempts, now()->addMinutes($this->lockoutMinutes));
    }

    /**
     * Reset attempts counter
     */
    protected function resetAttempts(string $key): void
    {
        cache()->forget($key . ':attempts');
        cache()->forget($key . ':locked_until');
    }

    /**
     * Lock the account
     */
    protected function lockAccount(string $key): void
    {
        cache()->put($key . ':locked_until', now()->addMinutes($this->lockoutMinutes), now()->addMinutes($this->lockoutMinutes));
    }

    /**
     * Get locked until timestamp
     */
    protected function getLockedUntil(string $key)
    {
        return cache()->get($key . ':locked_until');
    }
}