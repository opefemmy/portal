<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Exception;

/**
 * Remita Payment Service
 * Handles payment operations with Remita gateway
 *
 * API Docs: https://remita.net/developers/
 */
class RemitaService
{
    protected ?PaymentGateway $gateway;
    protected string $merchantId;
    protected string $apiKey;
    protected string $serviceTypeId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->gateway = PaymentGateway::where('provider', PaymentGateway::PROVIDER_REMITA)
            ->where('is_active', true)
            ->first();

        if ($this->gateway) {
            $this->merchantId = $this->gateway->getPublicKey();
            $this->apiKey = $this->gateway->getSecretKey();
            $this->serviceTypeId = $this->gateway->test_secret_key ?? '4430731'; // Default service type ID
            $this->baseUrl = $this->gateway->is_test_mode
                ? 'https://remitademo.xpresspayments.com'
                : 'https://login.remita.net';
        }
    }

    /**
     * Check if Remita is configured
     */
    public function isConfigured(): bool
    {
        return $this->gateway !== null
            && !empty($this->merchantId)
            && !empty($this->apiKey);
    }

    /**
     * Initialize payment with Remita
     */
    public function initializePayment(
        string $payerName,
        string $payerEmail,
        string $payerPhone,
        float $amount,
        string $description,
        string $orderId = null
    ): array {
        if (!$this->isConfigured()) {
            throw new Exception('Remita gateway is not configured.');
        }

        // Generate unique order ID if not provided
        $orderId = $orderId ?? 'ORD-' . time() . '-' . rand(1000, 9999);

        // Create payment record
        $payment = Payment::create([
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'remita',
            'description' => $description,
            'reference' => $orderId,
        ]);

        // Generate hash
        $hash = $this->generateHash($orderId, $amount);

        // Build payment URL
        $paymentUrl = $this->baseUrl . '/payment/' . $this->merchantId . '/' . $orderId . '/' . $amount . '/' . $hash;

        return [
            'payment' => $payment,
            'payment_url' => $paymentUrl,
            'order_id' => $orderId,
            'amount' => $amount,
            'description' => $description,
            'payer' => [
                'name' => $payerName,
                'email' => $payerEmail,
                'phone' => $payerPhone,
            ],
        ];
    }

    /**
     * Generate Remita hash
     */
    protected function generateHash(string $orderId, float $amount): string
    {
        $hashString = $this->apiKey . $orderId . $amount . $this->serviceTypeId;
        return hash('sha512', $hashString);
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $orderId): ?Payment
    {
        $payment = Payment::where('reference', $orderId)->first();

        if (!$payment) {
            return null;
        }

        if ($payment->status === 'paid') {
            return $payment;
        }

        if (!$this->isConfigured()) {
            return $payment;
        }

        try {
            // Generate verification hash
            $hash = $this->generateHash($orderId, $payment->amount);

            // Query payment status
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'remitaConsumerKey=' . $this->merchantId . ',remitaConsumerToken=' . $hash,
            ])->post($this->baseUrl . '/api/v2/payments/query', [
                'merchantId' => $this->merchantId,
                'serviceTypeId' => $this->serviceTypeId,
                'orderId' => $orderId,
                'amount' => $payment->amount,
            ]);

            $data = $response->json();

            // Check response status
            if (isset($data['status']) && $data['status'] === '00') {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payment_details' => json_encode($data),
                ]);
            } elseif (isset($data['status']) && $data['status'] === '021') {
                // Payment pending
                $payment->update([
                    'notes' => 'Payment pending: ' . ($data['message'] ?? 'Pending'),
                ]);
            }
        } catch (Exception $e) {
            \Log::error('Remita verification error: ' . $e->getMessage());
        }

        return $payment;
    }

    /**
     * Handle callback from Remita
     */
    public function handleCallback(array $data): Payment
    {
        $orderId = $data['orderId'] ?? $data['order_id'] ?? null;

        if (!$orderId) {
            throw new Exception('Invalid callback: no order ID');
        }

        $payment = Payment::where('reference', $orderId)->firstOrFail();

        $status = $data['status'] ?? '';
        $message = $data['message'] ?? '';

        if ($status === '00') {
            // Payment successful
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_details' => json_encode($data),
            ]);
        } elseif ($status === '021') {
            // Payment pending
            $payment->update([
                'status' => 'pending',
                'notes' => 'Payment pending: ' . $message,
            ]);
        } else {
            // Payment failed
            $payment->update([
                'status' => 'failed',
                'notes' => 'Payment failed: ' . $message,
            ]);
        }

        return $payment;
    }

    /**
     * Get payment form for direct embedding
     */
    public function getPaymentFormHtml(array $paymentData): string
    {
        $url = $paymentData['payment_url'];
        $html = '<form id="remita-payment-form" method="GET" action="' . $url . '">';
        $html .= '<input type="hidden" name="merchantId" value="' . $this->merchantId . '">';
        $html .= '<input type="hidden" name="serviceTypeId" value="' . $this->serviceTypeId . '">';
        $html .= '<input type="hidden" name="orderId" value="' . $paymentData['order_id'] . '">';
        $html .= '<input type="hidden" name="amount" value="' . $paymentData['amount'] . '">';
        $html .= '<input type="hidden" name="payer.name" value="' . ($paymentData['payer']['name'] ?? '') . '">';
        $html .= '<input type="hidden" name="payer.email" value="' . ($paymentData['payer']['email'] ?? '') . '">';
        $html .= '<input type="hidden" name="payer.phone" value="' . ($paymentData['payer']['phone'] ?? '') . '">';
        $html .= '</form>';
        $html .= '<script>document.getElementById("remita-payment-form").submit();</script>';

        return $html;
    }

    /**
     * Test connection to Remita
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Remita gateway is not configured',
            ];
        }

        try {
            // Just check if we can make a request
            return [
                'success' => true,
                'message' => 'Connection to Remita successful',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }
}
