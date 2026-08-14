<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Concerns\ResolvesInstitutionLogo;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Authenticated applicant-side receipt page.
 *
 * The existing `online-payment.receipt` route is PUBLIC — anyone with the
 * URL can render any payment's receipt. There's also no receipt at all
 * for `external_payments` (validated bank transfers / manual uploads).
 *
 * This controller is the applicant-portal equivalent of the public
 * online-payment.receipt: it accepts a polymorphic `{payment}` segment
 * which can resolve to either a `Payment.id` (online) or an
 * `ExternalPayment.id` (manual/bank-transfer), and enforces ownership —
 * the row's `payer_id` (Payment) or `applicant_id` (ExternalPayment)
 * must match the authenticated applicant's id.
 *
 * The receipt template (resources/views/applicant/payments/receipt.blade.php)
 * branches on `$isExternal` so a single view renders both flows.
 *
 * Slice 8i-applicant: trait gate at the top of show() — the applicant
 * routes use `auth` (not `role:applicant`), so this is the slug-level
 * check that protects against non-applicant authenticated users
 * reaching applicant endpoints.
 */
class PaymentReceiptController extends Controller
{
    use EnforcesPermission;
    use ResolvesInstitutionLogo;

    /**
     * Show the receipt for an online or manual payment belonging to the
     * authenticated applicant.
     */
    public function show(Request $request, string $payment): View
    {
        $this->requirePermission('applicant.payments.receipt');
        $applicant = Applicant::where('user_id', $request->user()->id)->firstOrFail();

        // Try online Payment first — applicant-payer_id is the
        // canonical ownership field for the applicant audience.
        $online = Payment::find((int) $payment);
        if ($online) {
            abort_unless(
                (int) $online->payer_id === (int) $applicant->id,
                403,
                'You do not have access to this payment receipt.'
            );

            return view('applicant.payments.receipt', [
                'applicant'    => $applicant,
                'payment'      => $online,
                'isExternal'   => false,
                'logoUrl'      => $this->resolveInstitutionLogoUrl(),
                'feeTypeLabel' => $this->resolveFeeTypeLabel($online),
                'payerMatric'  => $this->resolvePayerMatric($online),
            ]);
        }

        // Fall back to ExternalPayment (validated bank-transfer / manual).
        $external = ExternalPayment::find((int) $payment);
        if ($external) {
            abort_unless(
                (int) $external->applicant_id === (int) $applicant->id,
                403,
                'You do not have access to this payment receipt.'
            );

            return view('applicant.payments.receipt', [
                'applicant'  => $applicant,
                'payment'    => $external,
                'isExternal' => true,
                // External payments don't have a Fee catalogue link — the
                // view falls back to $payment->description / purpose. Pass
                // nulls so the partial paths skip rather than guess.
                'logoUrl'      => $this->resolveInstitutionLogoUrl(),
                'feeTypeLabel' => null,
                'payerMatric'  => null,
            ]);
        }

        abort(404, 'Payment not found.');
    }
}
