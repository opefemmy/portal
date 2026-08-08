<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Concerns\QueriesPaymentGateway;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Payment;
use App\Models\Student;
use App\Services\ApplicantPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * User-driven "recheck this payment with the gateway" action.
 *
 * Background: a payment row is created with `status='pending'` at
 * initiation (see Student\PaymentController::initiatePayment,
 * Applicant\PaymentService::initiate, OnlinePaymentController::processPayment)
 * and is supposed to be flipped to `verified` / `completed` when the
 * gateway callback fires. If the callback never lands (network drop,
 * browser closed, bank delay), the row stays `pending` forever and the
 * user has no recourse. This controller is that recourse: hit the
 * gateway once more from the server side, in the user's session, and
 * apply the same side effects the callback would have.
 *
 * Why a single endpoint (not one per audience):
 *   - The ownership rules mirror `PaymentReceiptPdfController::
 *     userCanViewReceipt`: student-or-applicant ownership, bursar
 *     school scope, super_admin global.
 *   - The gateway dispatch is identical regardless of who pressed the
 *     button — same trait, same call. Splitting by audience would force
 *     triplication.
 *   - The route is `auth`-only; ownership inside this controller is
 *     the gate.
 */
class PaymentRequeryController extends Controller
{
    use QueriesPaymentGateway;

    public function requery(Request $request, Payment $payment)
    {
        // Ownership check first — `abort_unless` throws HttpException
        // which the top-level Throwable catch below would otherwise
        // swallow and convert into a redirect. Run the ownership
        // check OUTSIDE the try block so an unauthorised user gets a
        // real 403 instead of being silently bounced back with a
        // flash they don't deserve to see.
        abort_unless(
            $this->userCanRequery($request, $payment),
            403,
            'You are not allowed to requery this payment.'
        );

        // Top-level Throwable catch — a downstream DB error or
        // gateway exception never surfaces as a 500; we log and
        // redirect back with a friendly flash.
        try {
            $redirect = $this->resolveRedirectTarget($request, $payment);

            // Already settled: no need to hammer the gateway. This
            // catches the case where the user clicks Requery twice
            // (first click succeeds, page reloads, second click).
            if (in_array($payment->status, ['verified', 'completed'], true)) {
                return redirect($redirect)
                    ->with('info', 'This payment is already settled — no need to requery.');
            }

            // Test-mode simulator has no gateway to call — bounce with
            // a useful message instead of pretending to verify.
            if ($payment->gateway === 'test') {
                return redirect($redirect)
                    ->with('info', 'Test payment — use the simulator to retry.');
            }

            $result = $this->verifyWithGateway($payment);

            if (! ($result['success'] ?? false)) {
                Log::info('payment requery: gateway said not settled', [
                    'payment_id' => $payment->id,
                    'gateway'    => $payment->gateway,
                    'message'    => $result['message'] ?? '(no message)',
                ]);
                return redirect($redirect)
                    ->with('warning', $result['message']
                        ?? 'Gateway has not settled this payment yet. Please try again in a few minutes.');
            }

            // Gateway confirmed. Apply side effects. The path depends
            // on which side of the portal owns the row:
            //   - applicant-side row → ApplicantPaymentService::markCompleted
            //     stamps applicant.*_paid_at and may run the
            //     applicant→student migration.
            //   - student-side row → straight update. Side effects
            //     (e.g. activateStudent for acceptance fees) live in
            //     the callback path and we don't need to re-run them
            //     here — by the time a student-side row is requeried,
            //     the activation step already happened in the original
            //     callback that we'd be requerying past.
            $payment = $this->applySuccess($payment, $result);

            return redirect($redirect)
                ->with('success', 'Payment verified successfully. Your receipt is now available.');
        } catch (\Throwable $e) {
            Log::error('payment requery: uncaught error', [
                'payment_id'      => $payment->id,
                'user_id'         => optional(Auth::user())->id,
                'exception_class' => get_class($e),
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with(
                'error',
                'We could not requery this payment just now. Please try again or contact the bursar.'
            );
        }
    }

    /**
     * Decide where the requery button should send the user back to.
     * Prefers the explicit `redirect_to` hidden input from the view
     * (so the flash lands on the history table they were looking at),
     * then falls back by audience.
     */
    private function resolveRedirectTarget(Request $request, Payment $payment): string
    {
        $explicit = $request->input('redirect_to');
        if ($explicit && filter_var($explicit, FILTER_VALIDATE_URL)) {
            return $explicit;
        }

        if ($payment->student_id) {
            return route('student.payments');
        }

        return route('applicant.payments.history');
    }

    /**
     * Stamp the row as verified and run the audience-specific side
     * effects. Mirrors the callback path's updates so the row ends up
     * in the same shape as if the original callback had landed.
     */
    private function applySuccess(Payment $payment, array $result): Payment
    {
        $transactionId = $result['transaction_id'] ?? null;

        // Applicant-side: route through the service so *_paid_at
        // stamps + the applicant→student migration fire. markCompleted
        // is idempotent (see ApplicantPaymentService::markCompleted),
        // so this is safe even if the row somehow already landed
        // status='completed'.
        if ($payment->student_type === 'applicant' || ! $payment->student_id) {
            return app(ApplicantPaymentService::class)->markCompleted($payment, [
                'transaction_id' => $transactionId,
                'requery'        => true,
                'verified_at'    => now()->toIso8601String(),
            ]);
        }

        // Student-side: direct update. The original callback
        // (`Student\PaymentController::verifyPaystackPayment`) does
        // the same dance, minus the activateStudent() call which
        // depends on a fee-name match against the live fee row — if
        // the original callback missed, the activate step missed too,
        // and we don't replay it from here (it should already have
        // landed if the row is genuinely settled).
        $payment->update([
            'status'         => 'verified',
            'transaction_id' => $transactionId ?: $payment->transaction_id,
            'paid_at'        => $payment->paid_at ?: now(),
            'is_verified'    => true,
        ]);

        return $payment->fresh();
    }

    /**
     * Same ownership pattern as PaymentReceiptPdfController::
     * userCanViewReceipt — copied rather than shared because the two
     * controllers have different scopes of concern (read-only view vs.
     * mutating action). Keep the rules in sync.
     */
    private function userCanRequery(Request $request, Payment $payment): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        // Student-side: must own the Student row that owns the payment.
        $student = Student::where('user_id', $user->id)->first();
        if ($student && (int) $payment->student_id === (int) $student->id) {
            return true;
        }

        // Bursar / registrar / admin: cross-school guard — must share
        // school_id with the payment's student (or be a super admin
        // with global reach).
        if ($user->hasAnyRole(['bursar', 'registrar', 'super_admin', 'admin'])) {
            $authSchoolId = $user->school_id;
            if (empty($authSchoolId) || $user->hasAnyRole(['super_admin'])) {
                return true;
            }
            if ($payment->student
                && (int) $payment->student->school_id === (int) $authSchoolId) {
                return true;
            }
        }

        // Applicant-side: must own the Applicant row that owns the
        // payment via payer_id.
        $applicant = Applicant::where('user_id', $user->id)->first();
        if ($applicant && (int) $payment->payer_id === (int) $applicant->id) {
            return true;
        }

        return false;
    }
}