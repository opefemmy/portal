<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        ]);
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'library.access' => \App\Http\Middleware\LibraryAccessMiddleware::class,
            'student.onboarding' => \App\Http\Middleware\StudentOnboardingComplete::class,
            'patient-portal' => \App\Http\Middleware\PatientPortalAuth::class,
            'applicant.paid' => \App\Http\Middleware\EnsureApplicantHasPaid::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Any exception that bubbles up out of an /applicant/* route is
        // caught by Laravel's exception handler. For applicant routes
        // specifically we want to redirect to the applicant dashboard
        // with a friendly error flash instead of showing the generic
        // exception page — the audience / per-purpose migrations or other
        // schema drift would otherwise produce a 500 that confuses the
        // applicant. The full trace is logged at the same time.
        $exceptions->render(function (\Throwable $e, $request) {
            if (! $request->is('applicant/*')) {
                return null;
            }

            // Authentication / authorization failures must propagate so
            // Laravel can redirect to the login page. Catching them here
            // and bouncing the user to /applicant/dashboard creates an
            // ERR_TOO_MANY_REDIRECTS loop, because the dashboard itself
            // requires auth — every hop is another AuthenticationException
            // that the handler re-redirects.
            if ($e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return null;
            }

            // 404s and validation failures should be handled by their own
            // dedicated renderers. Don't shadow them with our friendly
            // flash message.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                return null;
            }

            \Illuminate\Support\Facades\Log::error('applicant route: uncaught exception', [
                'path' => $request->path(),
                'method' => $request->method(),
                'exception_class' => get_class($e),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $flash = 'Something went wrong while processing your request. Please try again or contact the admissions office.';

            // Drive the message: for in-progress payment flows the user
            // just clicked Pay Now, so a payment-specific message reads
            // better.
            if ($request->is('applicant/payment/*') || $request->is('applicant/apply/payment*')) {
                $flash = 'We could not load the payment page. Please try again or contact the admissions office.';
            }

            // Redirecting to the dashboard when the failing route IS the
            // dashboard creates ERR_TOO_MANY_REDIRECTS — the handler fires
            // on every hop. If the request is for /applicant/dashboard
            // itself, render an error page instead of looping.
            $currentPath = trim($request->path(), '/');
            if ($currentPath === 'applicant/dashboard') {
                return response()->view('errors.500', ['exception' => $e], 500);
            }

            try {
                return redirect()->route('applicant.dashboard')->with('error', $flash);
            } catch (\Throwable $inner) {
                // Routes aren't always named in test env or during early
                // boot. Fall back to a hard-coded dashboard URL — guarded
                // against the same loop as above.
                if ($currentPath === 'applicant/dashboard') {
                    return response()->view('errors.500', ['exception' => $e], 500);
                }
                return redirect('/applicant/dashboard')->with('error', $flash);
            }
        });
    })->create();