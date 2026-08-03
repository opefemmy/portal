<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\PaymentType;
use App\Models\SystemSetting;
use App\Services\ApplicantPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentValidationController extends Controller
{
    public function __construct(private readonly ApplicantPaymentService $payments)
    {
    }

    /**
     * Show payment validation page (the bank-transfer entry point).
     */
    public function showValidatePayment()
    {
        if (! SystemSetting::isOpen('admission_form_open')) {
            return view('applicant.payment-closed', [
                'message' => 'Admission form is currently closed. Please check back later.',
            ]);
        }

        $applicant = Applicant::where('user_id', auth()->id())->first();
        if ($applicant && $applicant->hasPaid(PaymentType::PURPOSE_APPLICATION)) {
            return redirect()->route('applicant.apply')
                ->with('info', 'Your payment has already been verified. You can proceed with your application.');
        }

        $feeAmount = $this->payments->resolveAmount(PaymentType::PURPOSE_APPLICATION);
        $paymentPortalUrl = SystemSetting::getPaymentPortalUrl();

        return view('applicant.validate-payment', [
            'feeAmount' => $feeAmount,
            'paymentPortalUrl' => $paymentPortalUrl,
        ]);
    }

    /**
     * Validate a bank-transfer transaction ID entered by the applicant.
     *
     * Always validates against the application fee. Acceptance and
     * compulsory fees are validated through the same gateway page's
     * bank-transfer tab, not here — this entry point is the public
     * pre-login flow that already exists.
     */
    public function validatePayment(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string|min:5|max:100',
        ]);

        $transactionId = strtoupper(trim($request->transaction_id));

        $external = ExternalPayment::getValidPayment($transactionId);
        if (! $external) {
            // Friendly check for legacy applicants that recorded the ref on their own row.
            $legacy = Applicant::where('payment_ref', $transactionId)
                ->where('payment_status', 'completed')
                ->first();

            if ($legacy) {
                return back()->with('error', 'This is a legacy payment record. Please contact the admissions office for assistance.')
                    ->withInput();
            }

            return back()->with('error', 'Invalid Transaction ID. Kindly confirm your payment.')
                ->withInput();
        }

        $purpose = PaymentType::PURPOSE_APPLICATION;
        $expected = $this->payments->resolveAmount($purpose);
        if ($expected > 0 && $external->amount < $expected) {
            return back()->with('error', 'Payment amount is less than the required application fee of ₦' . number_format($expected))
                ->withInput();
        }

        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            $applicant = Applicant::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'status' => 'pending',
                'school_id' => \App\Models\School::first()?->id,
                'department_id' => \App\Models\Department::first()?->id,
                'programme_id' => \App\Models\Programme::first()?->id,
                'session_id' => \App\Models\Session::where('is_current', true)->first()?->id,
            ]);
        }

        $this->payments->recordManual($applicant, $external, $purpose);

        $external->markAsUsed($applicant->id, $user->id);

        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'payment_validated',
            'description' => 'Payment validated via transaction ID: ' . $transactionId,
            'metadata' => json_encode([
                'transaction_id' => $transactionId,
                'applicant_id' => $applicant->id,
                'amount' => $external->amount,
                'purpose' => $purpose,
            ]),
        ]);

        return redirect()->route('applicant.apply')
            ->with('success', 'Payment Verified Successfully! You can now complete your application.');
    }
}
