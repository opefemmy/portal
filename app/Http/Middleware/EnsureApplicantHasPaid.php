<?php

namespace App\Http\Middleware;

use App\Models\Applicant;
use App\Models\PaymentType;
use App\Services\ApplicantPaymentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate applicant routes by which fee the applicant has paid.
 *
 * Usage: ->middleware('applicant.paid:application')
 *        ->middleware('applicant.paid:acceptance')
 *        ->middleware('applicant.paid:compulsory')
 *
 * On failure, the user is redirected to the applicant dashboard with a
 * flash error explaining which step to complete first. Friendly view,
 * never a blank 403.
 */
class EnsureApplicantHasPaid
{
    public function __construct(private readonly ApplicantPaymentService $payments)
    {
    }

    public function handle(Request $request, Closure $next, string $purpose): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect('/login');
        }

        $applicant = Applicant::where('user_id', $user->id)->first();

        // First-time visitors with no applicant row yet — let them through;
        // the form itself will create the applicant.
        if (! $applicant) {
            return $next($request);
        }

        // Resolve the PaymentType so admin-defined purposes work without
        // touching this middleware. Falls back to the canonical three
        // purposes only when the lookup misses (e.g. an admin deleted the
        // row the route still references).
        $paymentType = \App\Models\PaymentType::findByPurpose($purpose);

        if (! $applicant->hasPaid($purpose)) {
            $label = $paymentType?->display_label ?? match ($purpose) {
                PaymentType::PURPOSE_APPLICATION => 'application fee',
                PaymentType::PURPOSE_ACCEPTANCE => 'acceptance fee',
                PaymentType::PURPOSE_SCHOOL_FEE => 'compulsory fee',
                default => $purpose,
            };

            $message = match ($purpose) {
                PaymentType::PURPOSE_ACCEPTANCE => "You must be admitted and pay the {$label} first.",
                PaymentType::PURPOSE_SCHOOL_FEE => 'Pay the acceptance fee before accessing this section.',
                default => "Pay the {$label} to continue.",
            };

            return redirect()->route('applicant.dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
