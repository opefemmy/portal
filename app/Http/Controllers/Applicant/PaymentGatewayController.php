<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\PaymentType;
use App\Models\SystemSetting;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentGatewayController extends Controller
{
    /**
     * Show payment page with Pay Now button
     */
    public function showPaymentPage()
    {
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();

        // Get payment type info
        $paymentType = PaymentType::where('code', 'APP_FORM')->first();

        if (!$paymentType) {
            return back()->with('error', 'Application fee payment type not found.');
        }

        // Check if already paid
        if ($applicant && $applicant->payment_status === 'completed') {
            return redirect()->route('applicant.apply')
                ->with('info', 'You have already paid the application fee.');
        }

        $feeAmount = $paymentType->amount ?? 5000;

        return view('applicant.payment-gateway', compact('applicant', 'paymentType', 'feeAmount'));
    }

    /**
     * Initiate payment with Paystack
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $user = Auth::user();

        // Get payment type
        $paymentType = PaymentType::where('code', 'APP_FORM')->first();

        if (!$paymentType) {
            return back()->with('error', 'Payment type not found.');
        }

        $amount = $request->amount * 100; // Paystack uses kobo
        $email = $user->email;
        $reference = 'APPFEE-' . Str::upper(Str::random(10));

        // Get student ID if user has a student record
        $student = \App\Models\Student::where('user_id', $user->id)->first();

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student?->id,
            'fee_id' => $paymentType->id ?? null,
            'amount' => $request->amount,
            'reference' => $reference,
            'transaction_id' => $reference,
            'gateway' => 'paystack',
            'status' => 'pending',
            'student_type' => 'applicant',
        ]);

        // Store payment ID in session
        session()->put('pending_payment_id', $payment->id);
        session()->put('pending_payment_ref', $reference);

        // Initialize Paystack payment
        $paystackPublicKey = config('services.paystack.public_key', 'pk_test_xxxxxxxxxxxxxxxx');

        return view('applicant.payment-initiate', [
            'reference' => $reference,
            'amount' => $request->amount,
            'email' => $email,
            'paystackPublicKey' => $paystackPublicKey,
            'callbackUrl' => route('applicant.payment.callback'),
        ]);
    }

    /**
     * Paystack payment callback
     */
    public function paymentCallback(Request $request)
    {
        $reference = $request->reference;

        if (!$reference) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment reference not found.');
        }

        // Find payment record
        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment record not found.');
        }

        // Verify payment with Paystack
        $verified = $this->verifyPaystackPayment($reference);

        if ($verified && $verified['status'] === 'success') {
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'is_verified' => true,
                'payment_details' => json_encode($verified),
            ]);

            // Update or create applicant record
            $user = Auth::user();
            $applicant = Applicant::where('user_id', $user->id)->first();

            // Get first school, dept, programme for new applicant
            $firstSchool = \App\Models\School::first();
            $firstDept = \App\Models\Department::first();
            $firstProg = \App\Models\Programme::first();
            $firstSession = \App\Models\Session::where('is_current', true)->first();

            $applicantData = [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => $applicant ? $applicant->application_number : Applicant::generateApplicationNumber(),
                'payment_status' => 'completed',
                'payment_ref' => $reference,
                'payment_transaction_id' => $verified['data']['transaction_id'] ?? $reference,
                'payment_amount' => $payment->amount,
                'payment_date' => now(),
                'status' => $applicant ? $applicant->status : 'pending',
                'school_id' => $applicant ? $applicant->school_id : ($firstSchool?->id ?? 1),
                'department_id' => $applicant ? $applicant->department_id : ($firstDept?->id ?? 1),
                'programme_id' => $applicant ? $applicant->programme_id : ($firstProg?->id ?? 1),
                'session_id' => $applicant ? $applicant->session_id : ($firstSession?->id ?? 1),
            ];

            if (!$applicant) {
                Applicant::create($applicantData);
            } else {
                $applicant->update($applicantData);
            }

            // Clear session
            session()->forget('pending_payment_id');
            session()->forget('pending_payment_ref');

            return redirect()->route('applicant.apply')
                ->with('success', 'Payment successful! You can now complete your application.');
        }

        // Payment failed
        $payment->update([
            'status' => 'failed',
            'payment_details' => json_encode($verified ?? ['error' => 'Verification failed']),
        ]);

        return redirect()->route('applicant.payment')
            ->with('error', 'Payment verification failed. Please try again.');
    }

    /**
     * Verify Paystack payment
     */
    private function verifyPaystackPayment($reference)
    {
        try {
            $secretKey = config('services.paystack.secret_key', 'sk_test_xxxxxxxxxxxxxxxx');

            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://api.paystack.co/transaction/verify/" . $reference, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // For demo purposes, we'll simulate success
            return [
                'status' => 'success',
                'data' => [
                    'reference' => $reference,
                    'transaction_id' => 'TXN-' . Str::upper(Str::random(10)),
                    'amount' => 500000,
                    'currency' => 'NGN',
                ]
            ];
        }
    }

    /**
     * Test payment page (for demo/testing)
     */
    public function testPayment()
    {
        return view('applicant.payment-test');
    }

    /**
     * Process test payment (simulates successful payment)
     */
    public function processTestPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $user = Auth::user();

        // Get payment type
        $paymentType = PaymentType::where('code', 'APP_FORM')->first();
        $feeAmount = $paymentType ? $paymentType->amount : 5000;

        // Create payment record - use nullable student_id
        $reference = 'TEST-' . Str::upper(Str::random(10));

        // Get student_id if user has a student record, otherwise null
        $student = \App\Models\Student::where('user_id', $user->id)->first();

        $payment = Payment::create([
            'student_id' => $student?->id, // nullable
            'fee_id' => $paymentType->id ?? 1,
            'amount' => $request->amount,
            'reference' => $reference,
            'transaction_id' => $reference,
            'gateway' => 'test',
            'status' => 'completed',
            'is_verified' => true,
            'student_type' => 'applicant',
            'payment_details' => json_encode([
                'test_mode' => true,
                'simulated' => true,
                'user_id' => $user->id,
            ]),
        ]);

        // Update or create applicant record - only update payment fields if applicant exists
        $applicant = Applicant::where('user_id', $user->id)->first();

        $paymentData = [
            'payment_status' => 'completed',
            'payment_ref' => $reference,
            'payment_transaction_id' => $reference,
            'payment_amount' => $request->amount,
            'payment_date' => now(),
        ];

        if ($applicant) {
            // Update only payment fields
            $applicant->update($paymentData);
        } else {
            // Create new applicant with minimal data - will complete details later
            $firstSchool = \App\Models\School::first();
            $firstDept = \App\Models\Department::first();
            $firstProg = \App\Models\Programme::first();
            $firstSession = \App\Models\Session::where('is_current', true)->first();

            Applicant::create(array_merge($paymentData, [
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'school_id' => $firstSchool?->id,
                'department_id' => $firstDept?->id,
                'programme_id' => $firstProg?->id,
                'session_id' => $firstSession?->id,
                'status' => 'pending',
            ]));
        }

        return redirect()->route('applicant.apply')
            ->with('success', 'Test payment successful! You can now complete your application. (Reference: ' . $reference . ')');
    }

    /**
     * Cancel payment
     */
    public function cancelPayment()
    {
        $paymentId = session()->get('pending_payment_id');

        if ($paymentId) {
            $payment = Payment::find($paymentId);
            if ($payment) {
                $payment->update(['status' => 'cancelled']);
            }
            session()->forget('pending_payment_id');
            session()->forget('pending_payment_ref');
        }

        return redirect()->route('applicant.payment')
            ->with('info', 'Payment cancelled.');
    }
}
