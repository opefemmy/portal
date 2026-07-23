<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ExternalPayment;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentValidationController extends Controller
{
    /**
     * Show payment validation page
     */
    public function showValidatePayment()
    {
        // Check if admission form is open
        if (!SystemSetting::isOpen('admission_form_open')) {
            return view('applicant.payment-closed', [
                'message' => 'Admission form is currently closed. Please check back later.'
            ]);
        }

        // Check if applicant already has valid payment
        $applicant = Applicant::where('user_id', auth()->id())->first();
        if ($applicant && $applicant->payment_status === 'completed') {
            return redirect()->route('applicant.apply')
                ->with('info', 'Your payment has already been verified. You can proceed with your application.');
        }

        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        return view('applicant.validate-payment', compact('requireFee', 'feeAmount'));
    }

    /**
     * Validate payment transaction ID
     */
    public function validatePayment(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|string|min:5|max:100',
            'email' => 'nullable|email',
        ]);

        $transactionId = strtoupper(trim($request->transaction_id));
        $email = $request->email;

        // Check if transaction ID exists
        $payment = ExternalPayment::where('transaction_id', $transactionId)->first();

        // If not found in external payments, check if it's in applicants table (legacy)
        if (!$payment) {
            $legacyPayment = Applicant::where('payment_ref', $transactionId)
                ->where('payment_status', 'completed')
                ->first();

            if ($legacyPayment) {
                // Legacy payment found - activate it
                return back()->with('error', 'This is a legacy payment record. Please contact the admissions office for assistance.');
            }

            return back()->with('error', 'Invalid Transaction ID. Kindly confirm your payment.')
                ->withInput();
        }

        // Check if already used
        if ($payment->is_used) {
            return back()->with('error', 'This payment reference has already been used.')
                ->withInput();
        }

        // Check payment status
        if ($payment->payment_status !== 'completed') {
            return back()->with('error', 'This payment is not completed. Status: ' . ucfirst($payment->payment_status))
                ->withInput();
        }

        // Check amount (if required fee is configured)
        $requireFee = SystemSetting::get(SystemSetting::ADMISSION_REQUIRE_FEE, 'false') === 'true';
        $feeAmount = SystemSetting::get(SystemSetting::ADMISSION_FEE_AMOUNT, 0);

        if ($requireFee && $feeAmount > 0) {
            if ($payment->amount < $feeAmount) {
                return back()->with('error', 'Payment amount is less than the required application fee of ₦' . number_format($feeAmount))
                    ->withInput();
            }
        }

        // Get or create applicant
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        if (!$applicant) {
            // Create new applicant record
            $applicant = Applicant::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'payment_status' => 'completed',
                'payment_ref' => $transactionId,
                'payment_transaction_id' => 'EXT-' . Str::random(12),
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->payment_date,
                'status' => 'pending',
            ]);
        } else {
            // Update existing applicant
            $applicant->update([
                'payment_status' => 'completed',
                'payment_ref' => $transactionId,
                'payment_transaction_id' => 'EXT-' . Str::random(12),
                'payment_amount' => $payment->amount,
                'payment_date' => $payment->payment_date,
            ]);
        }

        // Mark external payment as used
        $payment->markAsUsed($applicant->id, $user->id);

        // Log the validation
        \App\Models\ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'payment_validated',
            'description' => 'Payment validated via transaction ID: ' . $transactionId,
            'metadata' => json_encode([
                'transaction_id' => $transactionId,
                'applicant_id' => $applicant->id,
                'amount' => $payment->amount,
            ]),
        ]);

        return redirect()->route('applicant.apply')
            ->with('success', 'Payment Verified Successfully! You can now complete your application.');
    }
}
