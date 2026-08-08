<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Concerns\ResolvesInstitutionLogo;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Server-side PDF generation for payment receipts.
 *
 * Why a single endpoint (not one per audience):
 *   - All three receipt views (student, bursar, applicant) ultimately
 *     render the same `_receipt` partial. Splitting the route by role
 *     would force the ownership check to be triplicated.
 *   - The route is `auth`-only; the ownership check inside this
 *     controller is what gates which users can see which receipts.
 *
 * Ownership rules (in order):
 *   1. Student: must own a Student row whose id matches
 *      `payment.student_id`.
 *   2. Bursar/registrar/super_admin/admin: must share school_id with
 *      the payment's student (matches the per-role guard already used
 *      in `Student\PaymentController::printReceipt` and
 *      `Bursar\PaymentController::receipt`).
 *   3. Applicant: must own an Applicant row whose id matches
 *      `payment.payer_id`.
 *   4. Anyone else: 403.
 */
class PaymentReceiptPdfController extends Controller
{
    use ResolvesInstitutionLogo;

    public function download(Request $request, Payment $payment)
    {
        abort_unless(
            $this->userCanViewReceipt($request, $payment),
            403,
            'You are not allowed to view this receipt.'
        );

        try {
            $pdf = Pdf::loadView('payments._receipt_pdf', [
                'payment'      => $payment,
                'logoUrl'      => $this->resolveInstitutionLogoUrl(),
                'feeTypeLabel' => $this->resolveFeeTypeLabel($payment),
                'payerMatric'  => $this->resolvePayerMatric($payment),
            ])->setPaper('a4', 'portrait');

            $filename = 'receipt-' . ($payment->reference ?: $payment->id) . '.pdf';
            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            // DOMPDF can fail on missing images or malformed URLs in the
            // logo path. Fall back to a plain HTML view so the user
            // always gets something printable instead of a 500.
            Log::error('payment receipt PDF: render failed', [
                'payment_id' => $payment->id,
                'error'      => $e->getMessage(),
            ]);
            return response()->view('payments._receipt_pdf', [
                'payment'      => $payment,
                'logoUrl'      => null,
                'feeTypeLabel' => $this->resolveFeeTypeLabel($payment),
                'payerMatric'  => $this->resolvePayerMatric($payment),
            ]);
        }
    }

    /**
     * Centralised ownership check used by the PDF endpoint. Mirrors
     * the per-audience guards in Student\PaymentController::printReceipt,
     * Bursar\PaymentController::receipt, and Applicant\PaymentReceiptController::show.
     */
    private function userCanViewReceipt(Request $request, Payment $payment): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        // Student-side: the authenticated user must own the Student row
        // that owns this payment.
        $student = \App\Models\Student::where('user_id', $user->id)->first();
        if ($student && (int) $payment->student_id === (int) $student->id) {
            return true;
        }

        // Bursar / registrar / admin: cross-school guard — must share
        // school_id with the payment's student (matches the guard in
        // Bursar\PaymentController::receipt).
        if ($user->hasAnyRole(['bursar', 'registrar', 'super_admin', 'admin'])) {
            $authSchoolId = $user->school_id;
            // Super admins and users without a school_id can view any
            // receipt — they have global reach by design.
            if (empty($authSchoolId) || $user->hasAnyRole(['super_admin'])) {
                return true;
            }
            if ($payment->student
                && (int) $payment->student->school_id === (int) $authSchoolId) {
                return true;
            }
        }

        // Applicant-side: the authenticated user must own an Applicant
        // row whose id matches payment.payer_id.
        $applicant = \App\Models\Applicant::where('user_id', $user->id)->first();
        if ($applicant && (int) $payment->payer_id === (int) $applicant->id) {
            return true;
        }

        return false;
    }
}