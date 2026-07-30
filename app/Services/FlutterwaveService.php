<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * Flutterwave Payment Service
 * Handles payment operations with Flutterwave gateway
 */
class FlutterwaveService
{
    protected ?PaymentGateway $gateway;
    protected string $secretKey;
    protected string $publicKey;

    public function __construct()
    {
        $this->gateway = PaymentGateway::where('provider', PaymentGateway::PROVIDER_FLUTTERWAVE)
            ->where('is_active', true)
            ->first();

        if ($this->gateway) {
            $this->secretKey = $this->gateway->getSecretKey();
            $this->publicKey = $this->gateway->getPublicKey();
        }
    }

    /**
     * Check if Flutterwave is configured
     */
    public function isConfigured(): bool
    {
        return $this->gateway !== null && !empty($this->secretKey);
    }

    /**
     * Initialize payment with Flutterwave
     */
    public function initializePayment(Student $student, Fee $fee, float $amount, string $description = null): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('Flutterwave gateway is not configured.');
        }

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway' => 'flutterwave',
            'description' => $description ?? $fee->name,
            'session_id' => $student->session_id,
        ]);

        // Generate reference
        $reference = 'FLW-' . $payment->id . '-' . time();
        $payment->update(['payment_ref' => $reference]);

        // Build Flutterwave payment link
        $callbackUrl = url("/student/payments/verify?payment_id={$payment->id}");

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $reference,
                'amount' => $amount,
                'currency' => 'NGN',
                'redirect_url' => $callbackUrl,
                'customer' => [
                    'email' => $student->user->email,
                    'name' => $student->user->name,
                    'phonenumber' => $student->user->phone ?? '',
                ],
                'customizations' => [
                    'title' => config('app.name', 'EKSCOTECH'),
                    'description' => $description ?? $fee->name,
                ],
            ]);

            $data = $response->json();

            if ($data['status'] === 'success') {
                return [
                    'payment' => $payment,
                    'checkout_url' => $data['data']['link'],
                    'reference' => $reference,
                ];
            }

            throw new Exception($data['message'] ?? 'Failed to create payment link');
        } catch (Exception $e) {
            $payment->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            throw $e;
        }
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
            throw new Exception('Flutterwave gateway is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get("https://api.flutterwave.com/v3/transactions/verify_by_reference", [
                'tx_ref' => $reference,
            ]);

            $data = $response->json();

            if ($data['status'] === 'success' && $data['data']['status'] === 'successful') {
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
