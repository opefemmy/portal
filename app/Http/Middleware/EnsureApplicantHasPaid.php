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

        if (! in_array($purpose, [
            PaymentType::PURPOSE_APPLICATION,
            PaymentType::PURPOSE_ACCEPTANCE,
            PaymentType::PURPOSE_SCHOOL_FEE,
        ], true)) {
            abort(400, "Unknown payment purpose [$purpose].");
        }

        if (! $applicant->hasPaid($purpose)) {
            $message = match ($purpose) {
                PaymentType::PURPOSE_APPLICATION => 'Pay the application fee to continue.',
                PaymentType::PURPOSE_ACCEPTANCE => 'You must be admitted and pay the acceptance fee first.',
                PaymentType::PURPOSE_SCHOOL_FEE => 'Pay the acceptance fee before accessing this section.',
            };

            return redirect()->route('applicant.dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
