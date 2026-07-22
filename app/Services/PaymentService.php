<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\PaymentGateway;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * Payment Service
 * Handles all payment operations including Paystack integration
 */
class PaymentService
{
    /**
     * Initialize payment with Paystack
     */
    public function initializePayment(Student $student, Fee $fee, float $amount, string $description = null): array
    {
        $gateway = PaymentGateway::where('slug', 'paystack')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            throw new Exception('Paystack payment gateway is not configured.');
        }

        $settings = json_decode($gateway->settings, true);
        $secretKey = $settings['secret_key'] ?? null;

        if (!$secretKey) {
            throw new Exception('Paystack secret key not configured.');
        }

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'paystack',
            'description' => $description ?? $fee->name,
            'session_id' => $student->session_id,
        ]);

        // Initialize Paystack payment
        $callbackUrl = url("/student/payments/verify?payment_id={$payment->id}");

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => $student->user->email,
            'amount' => $amount * 100, // Convert to kobo
            'reference' => $payment->id . '_' . time(),
            'callback_url' => $callbackUrl,
            'metadata' => [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'fee_id' => $fee->id,
            ],
        ]);

        $data = $response->json();

        if (!$data['status']) {
            $payment->update(['status' => 'failed', 'notes' => $data['message'] ?? 'Failed to initialize payment']);
            throw new Exception($data['message'] ?? 'Failed to initialize payment');
        }

        // Update payment with reference
        $payment->update(['transaction_ref' => $data['data']['reference']]);

        return [
            'payment' => $payment,
            'authorization_url' => $data['data']['authorization_url'],
            'reference' => $data['data']['reference'],
        ];
    }

    /**
     * Verify Paystack payment
     */
    public function verifyPayment(string $reference): Payment
    {
        $payment = Payment::where('transaction_ref', $reference)->firstOrFail();

        if ($payment->status === 'paid') {
            return $payment;
        }

        $gateway = PaymentGateway::where('slug', 'paystack')
            ->where('is_active', true)
            ->first();

        if (!$gateway) {
            throw new Exception('Paystack payment gateway is not configured.');
        }

        $settings = json_decode($gateway->settings, true);
        $secretKey = $settings['secret_key'] ?? null;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get("https://api.paystack.co/transaction/verify", [
            'reference' => $reference,
        ]);

        $data = $response->json();

        if (!$data['status']) {
            throw new Exception('Unable to verify payment');
        }

        $transactionData = $data['data'];

        if ($transactionData['status'] === 'success') {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => $transactionData['id'],
                'notes' => 'Payment verified successfully via Paystack',
            ]);

            // Trigger any post-payment actions
            $this->handlePostPaymentActions($payment);
        } else {
            $payment->update([
                'status' => 'failed',
                'notes' => $transactionData['gateway_response'] ?? 'Payment not successful',
            ]);
        }

        return $payment;
    }

    /**
     * Handle post-payment actions
     */
    protected function handlePostPaymentActions(Payment $payment): void
    {
        // You can add custom logic here
        // e.g., send receipt email, update student status, etc.
    }

    /**
     * Get student's payment history
     */
    public function getPaymentHistory(int $studentId, int $sessionId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Payment::where('student_id', $studentId)
            ->with(['fee', 'fee.category'])
            ->orderBy('created_at', 'desc');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->get();
    }

    /**
     * Get outstanding payments
     */
    public function getOutstandingPayments(int $studentId): \Illuminate\Database\Eloquent\Collection
    {
        return Payment::where('student_id', $studentId)
            ->where('status', '!=', 'paid')
            ->where(function ($query) {
                $query->where('status', 'pending')
                    ->orWhere('status', 'failed');
            })
            ->with(['fee'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate total paid
     */
    public function calculateTotalPaid(int $studentId, int $sessionId = null): float
    {
        $query = Payment::where('student_id', $studentId)
            ->where('status', 'paid');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->sum('amount');
    }

    /**
     * Calculate outstanding balance
     */
    public function calculateOutstandingBalance(int $studentId, int $sessionId = null): float
    {
        $outstanding = Payment::where('student_id', $studentId)
            ->whereIn('status', ['pending', 'failed'])
            ->where('amount', '>', 0);

        if ($sessionId) {
            $outstanding->where('session_id', $sessionId);
        }

        return $outstanding->sum('amount');
    }

    /**
     * Generate payment receipt
     */
    public function generateReceipt(Payment $payment): array
    {
        $student = Student::with('user')->findOrFail($payment->student_id);

        return [
            'receipt_number' => 'RCP-' . strtoupper(uniqid()),
            'payment_id' => $payment->id,
            'date' => $payment->paid_at?->format('Y-m-d H:i:s'),
            'student' => [
                'name' => $student->user->name,
                'matric_number' => $student->matric_number,
                'department' => $student->programme?->name,
            ],
            'fee' => [
                'name' => $payment->fee->name ?? $payment->description,
                'category' => $payment->fee?->category,
            ],
            'amount' => $payment->amount,
            'status' => $payment->status,
            'transaction_ref' => $payment->transaction_ref,
            'institution' => [
                'name' => Setting::get('institution_name', 'EKSCOTECH'),
                'address' => Setting::get('institution_address', ''),
            ],
        ];
    }

    /**
     * Process bulk payments (for installment plans)
     */
    public function processInstallment(int $studentId, float $amount, string $description): Payment
    {
        return Payment::create([
            'student_id' => $studentId,
            'amount' => $amount,
            'status' => 'paid',
            'payment_method' => 'installment',
            'description' => $description,
            'paid_at' => now(),
        ]);
    }
}