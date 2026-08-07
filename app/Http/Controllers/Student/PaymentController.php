<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\SystemSetting;
use App\Services\SchoolFeeCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index()
    {
        // Check if payment is open
        if (!SystemSetting::isOpen('payment_open')) {
            return view('student.payments', [
                'fees' => collect([]),
                'payments' => collect([]),
                'error' => 'Payment portal is currently closed. Please check back later.'
            ]);
        }

        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = \App\Models\Session::getCurrentSession();

        $fees = Fee::where('session_id', $currentSession->id ?? 0)
            ->where(function ($query) use ($student) {
                $query->where('school_id', $student->school_id)
                    ->orWhereNull('school_id');
            })->where(function ($query) use ($student) {
                $query->where('department_id', $student->department_id)
                    ->orWhereNull('department_id');
            })->where(function ($query) use ($student) {
                $query->where('level', $student->level)
                    ->orWhereNull('level');
            })->get();

        $payments = Payment::where('student_id', $student->id)
            ->with('fee')
            ->latest()
            ->get();

        // Get active payment gateway
        $gateway = PaymentGateway::getActiveGateway();

        return view('student.payments', compact('fees', 'payments', 'gateway'));
    }

    public function pay(Fee $fee)
    {
        // Check if payment is open
        if (!SystemSetting::isOpen('payment_open')) {
            return back()->with('error', 'Payment portal is currently closed.');
        }

        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $category = $student->user?->isIndigene() ? 'indigene' : 'non_indigene';

        // Already fully paid? Send them back to the payments list with a
        // friendly message rather than rendering a useless form.
        if (SchoolFeeCalculator::totalPercentPaid($student, $fee) >= SchoolFeeCalculator::PERCENT_FULL) {
            return redirect()->route('student.payments')
                ->with('info', 'You have completed payment for this fee.');
        }

        $gateway = PaymentGateway::getActiveGateway();

        // Calculate pricing for each available percent option (100% / 60%
        // by default; just 40% if first installment is already paid).
        $percents = SchoolFeeCalculator::availablePercents($student, $fee);
        $pricing = [];
        foreach ($percents as $p) {
            $pricing[$p] = SchoolFeeCalculator::payable($student, $fee, $p);
        }

        return view('student.payment-pay', compact('fee', 'gateway', 'category', 'percents', 'pricing'));
    }

    /**
     * Initiate a student-side school-fee payment.
     *
     * Wrapped in a top-level Throwable catch so a downstream exception
     * (Paystack / Flutterwave 5xx, missing PaymentGateway row, missing
     * Fee id, ENUM truncation on `payments.student_type` etc.) never
     * surfaces as a raw 500 to the student. We log the exception and
     * bounce them back to the payments list with a generic flash so
     * they can retry.
     */
    public function initiatePayment(Request $request, Fee $fee)
    {
        try {
            return $this->initiatePaymentInner($request, $fee);
        } catch (\Throwable $e) {
            Log::error('student payment initiate: uncaught error', [
                'fee_id'           => $fee?->id,
                'user_id'          => optional(auth()->user())->id,
                'percent'          => $request->input('percent'),
                'exception_class'  => get_class($e),
                'error'            => $e->getMessage(),
                'trace'            => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('student.payments')
                ->with('error', 'We could not start your school-fee payment just now. Please try again or contact the bursar if the issue persists.');
        }
    }

    /**
     * Real implementation of initiatePayment — split out so the public
     * entry point can wrap it in a top-level Throwable catch and never 500.
     */
    private function initiatePaymentInner(Request $request, Fee $fee)
    {
        // Check if payment is open
        if (!SystemSetting::isOpen('payment_open')) {
            return back()->with('error', 'Payment portal is currently closed.');
        }

        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $gateway = PaymentGateway::getActiveGateway();

        if (!$gateway) {
            return back()->with('error', 'No payment gateway configured.');
        }

        $request->validate([
            'percent' => 'required|integer|in:100,60,40',
        ]);

        $percent = (int) $request->input('percent');

        // Hard-gate: reject any percent that is not on the calculator's
        // available list (e.g. user tries to pay 40% before 60%).
        $allowed = SchoolFeeCalculator::availablePercents($student, $fee);
        if (!in_array($percent, $allowed, true)) {
            return back()->with('error', 'That payment option is not available right now.');
        }

        $amount = SchoolFeeCalculator::payable($student, $fee, $percent);
        $penaltyAmount = 0;
        if (SystemSetting::get('payment_penalty', 'false') === 'true') {
            $penaltyAmount = (float) SystemSetting::get('payment_penalty_amount', 0);
        }

        $totalAmount = $amount + $penaltyAmount;
        $label = SchoolFeeCalculator::installmentLabel($percent);

        // Create payment record.
        //
        // NOTE on the installment columns: payments has TWO columns:
        //   - `installment_label` (varchar 20) — lowercase 'full'/'first'/'second',
        //     used by SchoolFeeCalculator and the reporting path.
        //   - `installment` (ENUM('First','Second','Full') on production)
        //     — uppercase, owned by the Bursar regime-payment domain
        //     (RegimeController).
        // We must NOT write `installment` here with a lowercase value —
        // strict mode rejects it as not in the ENUM and the INSERT
        // throws QueryException. That was the source of the
        // "student/payments/6/initiate server 500 error" the user
        // reported on production. The redundant label column already
        // carries the same information; leave `installment` alone.
        $payment = Payment::create([
            'student_id'        => $student->id,
            'fee_id'            => $fee->id,
            'amount'            => $amount,
            'portal_charge'     => (float) $fee->portal_charge,
            'total_amount'      => $totalAmount,
            'percent_paid'      => $percent,
            'installment_label' => $label,
            'reference'         => Payment::generateReference(),
            'gateway'           => $gateway->provider,
            'status'            => 'pending',
        ]);

        // Initialize payment based on gateway
        if ($gateway->provider === 'paystack') {
            return $this->initiatePaystackPayment($payment, $fee, $student, $gateway);
        } elseif ($gateway->provider === 'flutterwave') {
            return $this->initiateFlutterwavePayment($payment, $fee, $student, $gateway);
        }

        return back()->with('error', 'Unsupported payment gateway.');
    }

    protected function initiatePaystackPayment($payment, $fee, $student, $gateway)
    {
        $callbackUrl = route('student.payments.verify') . '?reference=' . $payment->reference;

        $data = [
            'email' => $student->user->email,
            'amount' => $payment->amount * 100, // Paystack expects kobo
            'reference' => $payment->reference,
            'callback_url' => $callbackUrl,
            'metadata' => [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'fee_id' => $fee->id,
            ],
        ];

        $secretKey = $gateway->getSecretKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.paystack.co/transaction/initialize', $data);

            $result = json_decode($response->body());

            // Guard against an empty / non-JSON body — the inner try/catch
            // only catches \Exception, but $result being null and reading
            // ->status on it throws an \Error under PHP 8 which slips past
            // this catch and lands in the outer "we could not start your
            // payment" flash at the initiate entry point. We saw this on
            // production when Paystack returned an empty body.
            if (is_object($result) && !empty($result->status)) {
                return redirect($result->data->authorization_url);
            }

            Log::error('Paystack initialization failed', ['response' => $result]);
            return back()->with('error', 'Payment initialization failed. Please try again.');
        } catch (\Throwable $e) {
            // Catch \Throwable (not \Exception) so PHP 8 \Errors — most
            // commonly "Attempt to read property on null" when the
            // gateway returns an unexpected body shape — stay inside
            // the friendly flash instead of bubbling up to the outer
            // generic catch.
            Log::error('Paystack error: ' . $e->getMessage());
            return back()->with('error', 'Payment error: ' . $e->getMessage());
        }
    }

    protected function initiateFlutterwavePayment($payment, $fee, $student, $gateway)
    {
        $callbackUrl = route('student.payments.verify') . '?reference=' . $payment->reference;

        $data = [
            'tx_ref' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => 'NGN',
            'redirect_url' => $callbackUrl,
            'customer' => [
                'email' => $student->user->email,
                'name' => $student->user->name,
            ],
            'meta' => [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
            ],
        ];

        $secretKey = $gateway->getSecretKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.flutterwave.com/v3/payments', $data);

            $result = json_decode($response->body());

            // Same null-safe guard as Paystack — see comment there.
            if (is_object($result) && ($result->status ?? null) === 'success') {
                return redirect($result->data->link);
            }

            Log::error('Flutterwave initialization failed', ['response' => $result]);
            return back()->with('error', 'Payment initialization failed. Please try again.');
        } catch (\Throwable $e) {
            Log::error('Flutterwave error: ' . $e->getMessage());
            return back()->with('error', 'Payment error: ' . $e->getMessage());
        }
    }

    public function verifyPayment(Request $request)
    {
        $reference = $request->reference;

        if (!$reference) {
            return redirect()->route('student.payments')
                ->with('error', 'Invalid payment reference.');
        }

        $payment = Payment::where('reference', $reference)->firstOrFail();
        $gateway = PaymentGateway::where('provider', $payment->gateway)->first();

        if (!$gateway) {
            return redirect()->route('student.payments')
                ->with('error', 'Payment gateway not found.');
        }

        // Verify based on gateway
        if ($gateway->provider === 'paystack') {
            return $this->verifyPaystackPayment($payment, $gateway);
        } elseif ($gateway->provider === 'flutterwave') {
            return $this->verifyFlutterwavePayment($payment, $gateway);
        }

        return redirect()->route('student.payments')
            ->with('error', 'Unsupported payment gateway.');
    }

    protected function verifyPaystackPayment($payment, $gateway)
    {
        $secretKey = $gateway->getSecretKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get('https://api.paystack.co/transaction/verify/' . $payment->reference);

            $result = json_decode($response->body());

            // Same null-safe guard as initiate — see initiatePaystackPayment.
            if (is_object($result) && !empty($result->status) && ($result->data->status ?? null) === 'success') {
                $payment->update([
                    'status' => 'verified',
                    'transaction_id' => $result->data->transaction_id,
                    'paid_at' => now(),
                ]);

                // Check if this is acceptance fee and auto-create student
                $fee = $payment->fee;
                if ($fee && in_array(strtolower($fee->name), ['acceptance fee', 'admission fee'])) {
                    $this->activateStudent($payment->student);
                }

                return redirect()->route('student.payments')
                    ->with('success', 'Payment verified successfully!');
            }

            $payment->update(['status' => 'failed']);
            return redirect()->route('student.payments')
                ->with('error', 'Payment verification failed.');
        } catch (\Throwable $e) {
            Log::error('Paystack verification error: ' . $e->getMessage());
            return redirect()->route('student.payments')
                ->with('error', 'Payment verification error.');
        }
    }

    protected function verifyFlutterwavePayment($payment, $gateway)
    {
        $secretKey = $gateway->getSecretKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get('https://api.flutterwave.com/v3/transactions/verify_by_ref?tx_ref=' . $payment->reference);

            $result = json_decode($response->body());

            // Same null-safe guard as initiate — see initiatePaystackPayment.
            if (is_object($result) && ($result->status ?? null) === 'success' && ($result->data->status ?? null) === 'successful') {
                $payment->update([
                    'status' => 'verified',
                    'transaction_id' => $result->data->id,
                    'paid_at' => now(),
                ]);

                return redirect()->route('student.payments')
                    ->with('success', 'Payment verified successfully!');
            }

            $payment->update(['status' => 'failed']);
            return redirect()->route('student.payments')
                ->with('error', 'Payment verification failed.');
        } catch (\Throwable $e) {
            Log::error('Flutterwave verification error: ' . $e->getMessage());
            return redirect()->route('student.payments')
                ->with('error', 'Payment verification error.');
        }
    }

    protected function activateStudent($student)
    {
        $student->update(['status' => 'active']);
        $student->user->update(['is_active' => true]);

        // If there's an applicant record, update their status too
        $applicant = \App\Models\Applicant::where('email', $student->user->email)->first();
        if ($applicant) {
            $applicant->update(['status' => 'admitted']);
        }
    }

    public function printReceipt(Payment $payment)
    {
        // Ownership check: a student must only view their own receipts.
        $student = Student::where('user_id', auth()->id())->first();
        if (!$student || $payment->student_id !== $student->id) {
            abort(403, 'You are not allowed to view this receipt.');
        }
        return view('student.payment-receipt', compact('payment'));
    }
}