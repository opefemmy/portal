<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\SystemSetting;
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

        // Check if already paid
        $existingPayment = Payment::where('student_id', $student->id)
            ->where('fee_id', $fee->id)
            ->where('status', 'verified')
            ->first();

        if ($existingPayment) {
            return redirect()->route('student.payments')
                ->with('info', 'This fee has already been paid.');
        }

        $gateway = PaymentGateway::getActiveGateway();

        return view('student.payment-pay', compact('fee', 'gateway'));
    }

    public function initiatePayment(Request $request, Fee $fee)
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

        // Check for penalty
        $penaltyAmount = 0;
        if (SystemSetting::get('payment_penalty', 'false') === 'true') {
            $penaltyAmount = SystemSetting::get('payment_penalty_amount', 0);
        }

        $totalAmount = $fee->amount + $penaltyAmount;

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => $totalAmount,
            'reference' => Payment::generateReference(),
            'gateway' => $gateway->provider,
            'status' => 'pending',
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

            if ($result->status) {
                return redirect($result->data->authorization_url);
            }

            Log::error('Paystack initialization failed', ['response' => $result]);
            return back()->with('error', 'Payment initialization failed. Please try again.');
        } catch (\Exception $e) {
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

            if ($result->status === 'success') {
                return redirect($result->data->link);
            }

            Log::error('Flutterwave initialization failed', ['response' => $result]);
            return back()->with('error', 'Payment initialization failed. Please try again.');
        } catch (\Exception $e) {
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

            if ($result->status && $result->data->status === 'success') {
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
        } catch (\Exception $e) {
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

            if ($result->status === 'success' && $result->data->status === 'successful') {
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
        } catch (\Exception $e) {
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
        return view('student.payment-receipt', compact('payment'));
    }
}