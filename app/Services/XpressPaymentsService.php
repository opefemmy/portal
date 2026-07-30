<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Fee;
use App\Models\Student;
use App\Models\PaymentGateway;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Exception;

/**
 * XpressPayments Payment Service
 * Handles payment operations with XpressPayments gateway
 *
 * API Docs: https://docs.xpresspayonline.com/docs/api-introduction
 */
class XpressPaymentsService
{
    protected ?PaymentGateway $gateway;
    protected string $baseUrl;
    protected string $publicKey;
    protected string $secretKey;

    public function __construct()
    {
        $this->gateway = PaymentGateway::where('provider', PaymentGateway::PROVIDER_XPRESSPAYMENTS)
            ->where('is_active', true)
            ->first();

        if ($this->gateway) {
            $this->publicKey = $this->gateway->getPublicKey();
            $this->secretKey = $this->gateway->getSecretKey();
            $this->baseUrl = $this->gateway->getBaseUrl();
        }
    }

    /**
     * Check if XpressPayments is configured
     */
    public function isConfigured(): bool
    {
        return $this->gateway !== null
            && !empty($this->publicKey)
            && !empty($this->secretKey);
    }

    /**
     * Initialize payment with XpressPayments
     * Uses Form Post Method - redirects user to payment page
     */
    public function initializePayment(Student $student, Fee $fee, float $amount, string $description = null): array
    {
        if (!$this->isConfigured()) {
            throw new Exception('XpressPayments gateway is not configured.');
        }

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'xpresspayments',
            'description' => $description ?? $fee->name,
            'session_id' => $student->session_id,
        ]);

        // Generate transaction reference
        $transactionRef = 'XPR-' . $payment->id . '-' . time();
        $payment->update(['transaction_ref' => $transactionRef]);

        // Build payment form data
        $callbackUrl = url("/student/payments/verify?payment_id={$payment->id}");

        // Generate hash for security
        $hashData = $this->publicKey . $transactionRef . $amount . $student->user->email . $callbackUrl;
        $hash = hash('sha512', $hashData . $this->secretKey);

        return [
            'payment' => $payment,
            'form_url' => $this->baseUrl . '/payments/form',
            'form_data' => [
                'publicKey' => $this->publicKey,
                'transactionId' => $transactionRef,
                'amount' => number_format($amount, 2, '.', ''),
                'email' => $student->user->email,
                'callbackUrl' => $callbackUrl,
                'hash' => $hash,
                'currency' => 'NGN',
                'country' => 'NG',
                'firstName' => explode(' ', $student->user->name)[0] ?? '',
                'lastName' => implode(' ', array_slice(explode(' ', $student->user->name), 1)) ?? '',
                'phoneNumber' => $student->user->phone ?? '',
                'logoURL' => asset('images/logo.png'),
                'meta' => json_encode([
                    'payment_id' => $payment->id,
                    'student_id' => $student->id,
                    'fee_id' => $fee->id,
                ]),
            ],
        ];
    }

    /**
     * Initialize payment for non-student (hospital, applicant, etc.)
     */
    public function initializeGenericPayment(
        string $email,
        string $name,
        float $amount,
        string $description,
        string $phone = null,
        array $metadata = []
    ): array {
        if (!$this->isConfigured()) {
            throw new Exception('XpressPayments gateway is not configured.');
        }

        // Create payment record
        $payment = Payment::create([
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'xpresspayments',
            'description' => $description,
            'notes' => json_encode($metadata),
        ]);

        // Generate transaction reference
        $transactionRef = 'XPR-GEN-' . $payment->id . '-' . time();
        $payment->update(['transaction_ref' => $transactionRef]);

        // Build payment form data
        $callbackUrl = url("/student/payments/verify?payment_id={$payment->id}");

        // Generate hash for security
        $hashData = $this->publicKey . $transactionRef . $amount . $email . $callbackUrl;
        $hash = hash('sha512', $hashData . $this->secretKey);

        $firstName = explode(' ', $name)[0] ?? '';
        $lastName = implode(' ', array_slice(explode(' ', $name), 1)) ?? '';

        return [
            'payment' => $payment,
            'form_url' => $this->baseUrl . '/payments/form',
            'form_data' => [
                'publicKey' => $this->publicKey,
                'transactionId' => $transactionRef,
                'amount' => number_format($amount, 2, '.', ''),
                'email' => $email,
                'callbackUrl' => $callbackUrl,
                'hash' => $hash,
                'currency' => 'NGN',
                'country' => 'NG',
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phoneNumber' => $phone ?? '',
                'logoURL' => asset('images/logo.png'),
                'meta' => json_encode(array_merge(['payment_id' => $payment->id], $metadata)),
            ],
        ];
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $transactionRef): Payment
    {
        $payment = Payment::where('transaction_ref', $transactionRef)->firstOrFail();

        if ($payment->status === 'paid') {
            return $payment;
        }

        if (!$this->isConfigured()) {
            throw new Exception('XpressPayments gateway is not configured.');
        }

        try {
            // Query payment status from XpressPayments
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/v1/payments/query', [
                'transactionId' => $transactionRef,
                'publicKey' => $this->publicKey,
            ]);

            $data = $response->json();

            if ($data['responseCode'] === '00') {
                // Payment successful
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'notes' => 'Payment verified via XpressPayments',
                ]);

                $this->handlePostPaymentActions($payment);
            } else {
                // Payment failed or pending
                $payment->update([
                    'status' => 'failed',
                    'notes' => $data['responseMessage'] ?? 'Payment verification failed',
                ]);
            }
        } catch (Exception $e) {
            // If we can't verify, mark as pending for manual verification
            $payment->update([
                'notes' => 'Verification pending: ' . $e->getMessage(),
            ]);
        }

        return $payment;
    }

    /**
     * Handle callback from XpressPayments after payment
     */
    public function handleCallback(array $data): Payment
    {
        $transactionRef = $data['transactionId'] ?? null;

        if (!$transactionRef) {
            throw new Exception('Invalid callback: no transaction ID');
        }

        $payment = Payment::where('transaction_ref', $transactionRef)->firstOrFail();

        $responseCode = $data['paymentResponseCode'] ?? $data['responseCode'] ?? '';
        $responseMessage = $data['paymentResponseMessage'] ?? $data['responseMessage'] ?? '';

        if ($responseCode === '000') {
            // Success
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'notes' => 'Payment successful: ' . $responseMessage,
            ]);

            $this->handlePostPaymentActions($payment);
        } elseif ($responseCode === '08') {
            // Pending - requires further validation
            $payment->update([
                'status' => 'pending',
                'notes' => 'Payment pending validation: ' . $responseMessage,
            ]);
        } else {
            // Failed
            $payment->update([
                'status' => 'failed',
                'notes' => 'Payment failed: ' . $responseMessage,
            ]);
        }

        return $payment;
    }

    /**
     * Handle post-payment actions
     */
    protected function handlePostPaymentActions(Payment $payment): void
    {
        // Add custom logic here
        // e.g., send receipt email, update student status, etc.
    }

    /**
     * Get payment form HTML for direct embedding
     */
    public function getPaymentFormHtml(array $formData): string
    {
        $formHtml = '<form id="xpresspayment-form" method="POST" action="' . $formData['form_url'] . '">';

        foreach ($formData['form_data'] as $key => $value) {
            $formHtml .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
        }

        $formHtml .= '</form>';
        $formHtml .= '<script>document.getElementById("xpresspayment-form").submit();</script>';

        return $formHtml;
    }

    /**
     * Test connection to XpressPayments
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'XpressPayments gateway is not configured',
            ];
        }

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/v1/payments/query', [
                'transactionId' => 'TEST-' . time(),
                'publicKey' => $this->publicKey,
            ]);

            // Any response means connection is working
            return [
                'success' => true,
                'message' => 'Connection to XpressPayments successful',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
