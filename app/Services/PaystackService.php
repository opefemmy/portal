<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Paystack Payment Service
 * Handles payment operations with Paystack gateway
 */
class PaystackService
{
    protected ?PaymentGateway $gateway;
    protected string $secretKey;
    protected string $publicKey;

    public function __construct()
    {
        $this->gateway = PaymentGateway::where('provider', PaymentGateway::PROVIDER_PAYSTACK)
            ->where('is_active', true)
            ->first();

        if ($this->gateway) {
            $this->secretKey = $this->gateway->getSecretKey();
            $this->publicKey = $this->gateway->getPublicKey();
        }
    }

    /**
     * Check if Paystack is configured
     */
    public function isConfigured(): bool
    {
        return $this->gateway !== null && !empty($this->secretKey);
    }

    /**
     * Initialize payment with Paystack
     */
    public function initializePayment(Student $student, Fee $fee, float $amount, string $description = null): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Paystack gateway is not configured.');
        }

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway' => 'paystack',
            'description' => $description ?? $fee->name,
            'session_id' => $student->session_id,
        ]);

        // Generate reference
        $reference = 'PSK-' . $payment->id . '-' . time();
        $payment->update(['payment_ref' => $reference]);

        // Build Paystack checkout URL
        $callbackUrl = url("/student/payments/verify?payment_id={$payment->id}");

        $checkoutUrl = "https://checkout.paystack.co/?";
        $checkoutUrl .= "email=" . urlencode($student->user->email);
        $checkoutUrl .= "&amount=" . ($amount * 100); // Paystack uses kobo
        $checkoutUrl .= "&reference=" . $reference;
        $checkoutUrl .= "&callback_url=" . urlencode($callbackUrl);

        return [
            'payment' => $payment,
            'checkout_url' => $checkoutUrl,
            'reference' => $reference,
        ];
    }

    /**
     * Verify payment
     */
    public function verifyPayment(string $reference): Payment
    {
        $payment = Payment::where('payment_ref', $reference)->firstOrFail();

        if ($payment->status === 'completed') {
            return $payment;
        }

        if (!$this->isConfigured()) {
            throw new Exception('Paystack gateway is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get("https://api.paystack.co/transaction/verify", [
                'reference' => $reference,
            ]);

            $data = $response->json();

            if ($data['status'] === true && $data['data']['status'] === 'success') {
                $payment->update([
                    'status' => 'completed',
                    'payment_details' => json_encode($data),
                ]);
            } else {
                $payment->update([
                    'status' => 'failed',
                    'payment_details' => json_encode($data),
                ]);
            }
        } catch (Exception $e) {
            $payment->update([
                'status' => 'failed',
                'notes' => 'Verification failed: ' . $e->getMessage(),
            ]);
        }

        return $payment;
    }

    /**
     * Get public key for frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey ?? '';
    }
}
