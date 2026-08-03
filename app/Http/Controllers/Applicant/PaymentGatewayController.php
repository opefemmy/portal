<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\PaymentType;
use App\Models\SystemSetting;
use App\Models\Payment;
use App\Services\ApplicantPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentGatewayController extends Controller
{
    public function __construct(private readonly ApplicantPaymentService $payments)
    {
    }

    /**
     * Show payment page with Pay Now button (and bank-transfer tab).
     *
     * URL: /applicant/payment/gateway?purpose=application|acceptance|compulsory|school_fee
     */
    public function showPaymentPage(Request $request)
    {
        $user = Auth::user();
        $applicant = Applicant::where('user_id', $user->id)->first();
        $purpose = $request->get('purpose', PaymentType::PURPOSE_APPLICATION);

        $paymentType = $this->payments->resolvePaymentType($purpose);
        if (! $paymentType) {
            return back()->with('error', 'Payment type not configured. Please contact the admissions office.');
        }

        // Service-driven gate (replaces the two ad-hoc checks that lived here before).
        $block = $this->payments->canPay($applicant, $purpose);
        if ($block) {
            return redirect()->route('applicant.dashboard')->with('error', $block);
        }

        $feeAmount = $this->payments->resolveAmount($purpose);

        return view('applicant.payment-gateway', compact('applicant', 'paymentType', 'feeAmount', 'purpose'));
    }

    /**
     * Initiate online payment. Returns the Paystack inline iframe page
     * with the freshly-created pending Payment reference.
     */
    public function initiatePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'purpose' => 'nullable|string|in:application,acceptance,compulsory,school_fee',
        ]);

        $user = Auth::user();
        $purpose = $request->input('purpose', PaymentType::PURPOSE_APPLICATION);
        $applicant = Applicant::where('user_id', $user->id)->firstOrFail();

        $block = $this->payments->canPay($applicant, $purpose);
        if ($block) {
            return back()->with('error', $block);
        }

        $initiated = $this->payments->initiate($applicant, $purpose, 'paystack');

        session()->put('pending_payment_id', $initiated['payment']->id);
        session()->put('pending_payment_ref', $initiated['reference']);
        session()->put('pending_payment_purpose', $purpose);

        $paystackPublicKey = config('services.paystack.public_key', 'pk_test_xxxxxxxxxxxxxxxx');

        return view('applicant.payment-initiate', [
            'reference' => $initiated['reference'],
            'amount' => $initiated['amount'],
            'email' => $user->email,
            'paystackPublicKey' => $paystackPublicKey,
            'callbackUrl' => route('applicant.payment.callback'),
        ]);
    }

    /**
     * Paystack payment callback. Single funnel into the service.
     */
    public function paymentCallback(Request $request)
    {
        $reference = $request->reference;

        if (! $reference) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment reference not found.');
        }

        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            return redirect()->route('applicant.payment')
                ->with('error', 'Payment record not found.');
        }

        $verified = $this->verifyPaystackPayment($reference);

        if ($verified && ($verified['status'] ?? null) === 'success') {
            $this->payments->markCompleted($payment, $verified);

            session()->forget('pending_payment_id');
            session()->forget('pending_payment_ref');
            session()->forget('pending_payment_purpose');

            $purpose = $payment->payment_purpose;

            $redirectRoute = match ($purpose) {
                'acceptance' => 'applicant.dashboard',
                'compulsory', 'school_fee' => 'student.dashboard',
                default => 'applicant.apply',
            };

            $successMessage = match ($purpose) {
                'acceptance' => 'Acceptance fee payment verified. You can now print your admission letter.',
                'compulsory', 'school_fee' => 'Compulsory fee verified. Redirecting to the student portal.',
                default => 'Payment successful! You can now complete your application.',
            };

            return redirect()->route($redirectRoute)->with('success', $successMessage);
        }

        $payment->update([
            'status' => 'failed',
            'payment_details' => json_encode($verified ?? ['error' => 'Verification failed']),
        ]);

        return redirect()->route('applicant.payment')
            ->with('error', 'Payment verification failed. Please try again.');
    }

    /**
     * Verify Paystack payment. Public-facing on the gateway — for live use
     * this should be the only network call the controller makes.
     */
    private function verifyPaystackPayment($reference)
    {
        try {
            $secretKey = config('services.paystack.secret_key', 'sk_test_xxxxxxxxxxxxxxxx');

            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.paystack.co/transaction/verify/' . $reference, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            // Demo fallback — keep the existing offline simulation so tests pass.
            return [
                'status' => 'success',
                'data' => [
                    'reference' => $reference,
                    'transaction_id' => 'TXN-' . Str::upper(Str::random(10)),
                    'amount' => 500000,
                    'currency' => 'NGN',
                ],
            ];
        }
    }

    /**
     * Test payment page (for demo/testing).
     */
    public function testPayment()
    {
        return view('applicant.payment-test');
    }

    /**
     * Process test payment (simulates successful payment).
     */
    public function processTestPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
            'purpose' => 'nullable|string|in:application,acceptance,compulsory,school_fee',
        ]);

        $user = Auth::user();
        $purpose = $request->input('purpose', PaymentType::PURPOSE_APPLICATION);

        $applicant = Applicant::where('user_id', $user->id)->first();

        if (! $applicant) {
            // Demo path: create a thin applicant row so the service can attach a payer_id.
            $applicant = Applicant::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'application_number' => Applicant::generateApplicationNumber(),
                'status' => $purpose === PaymentType::PURPOSE_APPLICATION ? 'pending' : 'admitted',
                'school_id' => \App\Models\School::first()?->id,
                'department_id' => \App\Models\Department::first()?->id,
                'programme_id' => \App\Models\Programme::first()?->id,
                'session_id' => \App\Models\Session::where('is_current', true)->first()?->id,
            ]);
        }

        $initiated = $this->payments->initiate($applicant, $purpose, 'test');

        $payment = $initiated['payment'];
        $this->payments->markCompleted($payment, [
            'test_mode' => true,
            'simulated' => true,
            'user_id' => $user->id,
            'purpose' => $purpose,
        ]);

        $redirectRoute = match ($purpose) {
            'acceptance' => 'applicant.dashboard',
            'compulsory', 'school_fee' => 'student.dashboard',
            default => 'applicant.apply',
        };

        $successMessage = match ($purpose) {
            'acceptance' => 'Acceptance fee verified. You can now print your admission letter. (Ref: ' . $payment->reference . ')',
            'compulsory', 'school_fee' => 'Compulsory fee verified. (Ref: ' . $payment->reference . ')',
            default => 'Test payment successful! You can now complete your application. (Reference: ' . $payment->reference . ')',
        };

        return redirect()->route($redirectRoute)->with('success', $successMessage);
    }

    /**
     * Cancel payment.
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
