<?php

namespace App\Http\Controllers;

use App\Models\Hospital\HospitalServiceType;
use App\Models\Hospital\HospitalPayment;
use App\Services\Hospital\HospitalPaymentNotificationService;
use App\Services\Hospital\PharmacyAutoDispenseService;
use App\Services\Hospital\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class HospitalPaymentController extends Controller
{
    /**
     * Get all active service types
     */
    public function getServiceTypes()
    {
        $services = HospitalServiceType::active()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return response()->json([
            'success' => true,
            'services' => $services,
        ]);
    }

    /**
     * Process hospital payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'patient_name' => 'required|string|max:255',
            'patient_email' => 'nullable|email',
            'patient_phone' => 'required|string|max:20',
            'patient_gender' => 'nullable|string|max:20',
            'patient_age' => 'nullable|integer|min:0|max:150',
            'service_type_id' => 'required|exists:hospital_service_types,id',
            'payment_method' => 'required|in:online,bank_transfer',
            'appointment_date' => 'nullable|date|after_or_equal:today',
            'doctor_name' => 'nullable|string|max:255',
            'gateway' => 'nullable|string|in:xpresspayments,paystack,flutterwave,remita',
        ]);

        // Get service details
        $service = HospitalServiceType::findOrFail($request->service_type_id);

        // Calculate portal charge (2%)
        $portalCharge = ($service->amount * 2) / 100;
        $totalAmount = $service->amount + $portalCharge;

        // Generate unique payment reference
        $paymentRef = 'HSP-' . strtoupper(Str::random(10));

        // Get selected gateway (default to xpresspayments)
        $gateway = $request->gateway ?? 'xpresspayments';

        // Retry behaviour: if the same patient already has a pending or
        // failed payment for the same service, reuse that row instead of
        // inserting a duplicate. Key is (patient_phone, service_type_id)
        // — admin/staff booking flow uses the typed-in phone rather than
        // a session-scoped external patient. Cancelled rows still get a
        // fresh row — they represent intentional aborts.
        $existing = HospitalPayment::where('patient_phone', $request->patient_phone)
            ->where('service_type_id', $service->id)
            ->whereIn('status', [HospitalPayment::STATUS_PENDING, HospitalPayment::STATUS_FAILED])
            ->latest('created_at')
            ->first();

        if ($existing) {
            // Refresh the reference so the gateway init is fresh.
            $paymentRef = 'HSP-' . strtoupper(Str::random(10));

            $existing->update([
                'payment_ref'      => $paymentRef,
                'amount'           => $service->amount,
                'portal_charge'    => $portalCharge,
                'total_amount'     => $totalAmount,
                'payment_method'   => $request->payment_method,
                'status'           => HospitalPayment::STATUS_PENDING,
                'payment_date'     => now()->toDateString(),
                'appointment_date' => $request->appointment_date ? \Carbon\Carbon::parse($request->appointment_date)->format('Y-m-d H:i:s') : null,
                'doctor_name'      => $request->doctor_name,
                'notes'            => $request->notes,
            ]);

            $payment = $existing;
        } else {
            // Create payment record
            $payment = HospitalPayment::create([
                'payment_ref' => $paymentRef,
                'patient_name' => $request->patient_name,
                'patient_email' => $request->patient_email,
                'patient_phone' => $request->patient_phone,
                'patient_gender' => $request->patient_gender,
                'patient_age' => $request->patient_age,
                'service_type_id' => $service->id,
                'service_name' => $service->name,
                'amount' => $service->amount,
                'portal_charge' => $portalCharge,
                'total_amount' => $totalAmount,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'payment_date' => now()->toDateString(),
                'appointment_date' => $request->appointment_date ? \Carbon\Carbon::parse($request->appointment_date)->format('Y-m-d H:i:s') : null,
                'doctor_name' => $request->doctor_name,
                'notes' => $request->notes,
            ]);
        }

        // Build response with gateway info
        $response = [
            'success' => true,
            'message' => 'Payment initiated successfully!',
            'payment_id' => $payment->id,
            'reference' => $paymentRef,
            'amount' => $totalAmount,
            'gateway' => $gateway,
            'receipt_url' => route('hospital-payment.receipt', $payment->id),
        ];

        // For online payments, provide redirect info
        if ($request->payment_method === 'online') {
            $response['redirect_to_payment'] = true;
            $response['payment_url'] = route('hospital-payment.receipt', $payment->id) . '?pay=1';
        }

        return response()->json($response);
    }

    /**
     * Validate payment by reference - verifies with gateway and updates status
     */
    public function validatePayment(Request $request)
    {
        $request->validate([
            'payment_reference' => 'required|string',
        ]);

        $payment = HospitalPayment::where('payment_ref', $request->payment_reference)
            ->orWhere('payment_ref', 'like', '%' . $request->payment_reference . '%')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found. Please check your reference and try again.',
            ]);
        }

        // If payment is already completed, return success
        if ($payment->status === 'completed') {
            return response()->json([
                'success' => true,
                'payment' => $this->formatPaymentResponse($payment),
            ]);
        }

        // If payment method is online, try to verify with gateway
        if ($payment->payment_method === 'online') {
            $verifiedStatus = $this->verifyWithGateway($payment);
            if ($verifiedStatus) {
                $payment->refresh();
            }
        }

        return response()->json([
            'success' => true,
            'payment' => $this->formatPaymentResponse($payment),
        ]);
    }

    /**
     * Verify payment with payment gateway
     */
    protected function verifyWithGateway(HospitalPayment $payment): bool
    {
        // Get gateway settings from database
        $gateway = \App\Models\PaymentGateway::where('is_active', true)->first();

        if (!$gateway) {
            return false;
        }

        $wasCompleted = $payment->status === HospitalPayment::STATUS_COMPLETED;

        try {
            if ($gateway->provider === 'xpresspayments') {
                $verified = $this->verifyXpressPayment($payment, $gateway);
            } elseif ($gateway->provider === 'paystack') {
                $verified = $this->verifyPaystackPayment($payment, $gateway);
            } elseif ($gateway->provider === 'flutterwave') {
                $verified = $this->verifyFlutterwavePayment($payment, $gateway);
            } elseif ($gateway->provider === 'remita') {
                $verified = $this->verifyRemitaPayment($payment, $gateway);
            } else {
                $verified = false;
            }
        } catch (\Exception $e) {
            \Log::error('Payment verification failed: ' . $e->getMessage());
            return false;
        }

        // If the payment just transitioned to completed, auto-dispense pharmacy drugs
// and notify the receiving office. Both hooks are failure-tolerant so a bug in
// either never breaks payment verification.
        if ($verified && !$wasCompleted && $payment->fresh()->status === HospitalPayment::STATUS_COMPLETED) {
            try {
                app(PharmacyAutoDispenseService::class)->dispenseOnPayment($payment->fresh());
            } catch (\Exception $e) {
                \Log::error('Pharmacy auto-dispense failed: ' . $e->getMessage());
            }
            try {
                HospitalPaymentNotificationService::notifyPaymentCompleted($payment->fresh());
            } catch (\Exception $e) {
                \Log::error('Hospital payment notification failed: ' . $e->getMessage());
            }
            // Mark any HospitalOrderItem rows linked to this payment as paid so
            // they appear on the pharmacy / lab queues.
            try {
                app(OrderFulfillmentService::class)->fulfillOnPayment($payment->fresh());
            } catch (\Exception $e) {
                \Log::error('Hospital order fulfilment failed: ' . $e->getMessage());
            }
        }

        return $verified;
    }

    /**
     * Verify with XpressPayments
     */
    protected function verifyXpressPayment(HospitalPayment $payment, $gateway): bool
    {
        $publicKey = $gateway->getPublicKey();
        $secretKey = $gateway->getSecretKey();
        $baseUrl = $gateway->getBaseUrl();

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/api/v1/payments/query', [
                'transactionId' => $payment->payment_ref,
                'publicKey' => $publicKey,
            ]);

            $data = $response->json();

            if (isset($data['responseCode']) && $data['responseCode'] === '00') {
                $payment->update([
                    'status' => 'completed',
                    'notes' => 'Verified via XpressPayments: ' . ($data['responseMessage'] ?? 'Success'),
                ]);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('XpressPayment verification error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Verify with Paystack
     */
    protected function verifyPaystackPayment(HospitalPayment $payment, $gateway): bool
    {
        $secretKey = $gateway->getSecretKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get('https://api.paystack.co/transaction/verify/' . $payment->payment_ref);

            $data = $response->json();

            if ($data['status'] === true && $data['data']['status'] === 'success') {
                $payment->update([
                    'status' => 'completed',
                    'notes' => 'Verified via Paystack: ' . ($data['data']['gateway_response'] ?? 'Success'),
                ]);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Paystack verification error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Verify with Flutterwave
     */
    protected function verifyFlutterwavePayment(HospitalPayment $payment, $gateway): bool
    {
        $secretKey = $gateway->getSecretKey();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secretKey,
            ])->get('https://api.flutterwave.com/v3/transactions/' . $payment->payment_ref . '/verify');

            $data = $response->json();

            if ($data['status'] === 'success' && $data['data']['status'] === 'successful') {
                $payment->update([
                    'status' => 'completed',
                    'notes' => 'Verified via Flutterwave: ' . ($data['data']['processor_response'] ?? 'Success'),
                ]);
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Flutterwave verification error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Verify with Remita
     */
    protected function verifyRemitaPayment(HospitalPayment $payment, $gateway): bool
    {
        $merchantId = $gateway->getPublicKey();
        $apiKey = $gateway->getSecretKey();
        $serviceTypeId = $gateway->test_secret_key ?? '4430731';
        $baseUrl = $gateway->getBaseUrl();

        try {
            // Generate hash
            $hashString = $apiKey . $payment->payment_ref . $payment->total_amount . $serviceTypeId;
            $hash = hash('sha512', $hashString);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'remitaConsumerKey=' . $merchantId . ',remitaConsumerToken=' . $hash,
            ])->post($baseUrl . '/api/v2/payments/query', [
                'merchantId' => $merchantId,
                'serviceTypeId' => $serviceTypeId,
                'orderId' => $payment->payment_ref,
                'amount' => $payment->total_amount,
            ]);

            $data = $response->json();

            // Status 00 = successful, 021 = pending
            if (isset($data['status']) && $data['status'] === '00') {
                $payment->update([
                    'status' => 'completed',
                    'notes' => 'Verified via Remita: ' . ($data['message'] ?? 'Success'),
                ]);
                return true;
            } elseif (isset($data['status']) && $data['status'] === '021') {
                // Payment pending
                $payment->update([
                    'notes' => 'Payment pending: ' . ($data['message'] ?? 'Pending'),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Remita verification error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Format payment response
     */
    protected function formatPaymentResponse(HospitalPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'reference' => $payment->payment_ref,
            'patient_name' => $payment->patient_name,
            'patient_email' => $payment->patient_email,
            'patient_phone' => $payment->patient_phone,
            'service_name' => $payment->service_name,
            'amount' => $payment->amount,
            'portal_charge' => $payment->portal_charge,
            'total_amount' => $payment->total_amount,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'created_at' => $payment->created_at->format('d M Y, h:i A'),
        ];
    }

    /**
     * Check payment status via reference (GET request)
     */
    public function checkPayment($reference)
    {
        $payment = HospitalPayment::where('payment_ref', $reference)
            ->orWhere('payment_ref', 'like', '%' . $reference . '%')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ]);
        }

        // If payment is pending, try to verify
        if ($payment->status === 'pending' && $payment->payment_method === 'online') {
            $this->verifyWithGateway($payment);
            $payment->refresh();
        }

        return response()->json([
            'success' => true,
            'payment' => $this->formatPaymentResponse($payment),
        ]);
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(HospitalPayment $payment)
    {
        // If payment is pending and online, try to verify first
        if ($payment->status === 'pending' && $payment->payment_method === 'online') {
            $this->verifyWithGateway($payment);
            $payment->refresh();
        }

        return view('hospital-payment.receipt', compact('payment'));
    }

    /**
     * Look up recent hospital payments by patient phone number.
     *
     * Public endpoint (no login required) so a payer who lost their receipt
     * URL can still retrieve the last 10 payments they made with their
     * phone number. Each row links to `hospital-payment.receipt`, which
     * is also public and renders the printable receipt.
     *
     * The phone match is exact — we don't expose anyone else's payments
     * because the lookup is keyed off the phone number which only the
     * payer knows. We deliberately don't include patient_email /
     * patient_name lookups here because phone is the most common
     * identifier given at payment time (collected on the gateway form).
     */
    public function historyByPhone(Request $request)
    {
        $phone = trim((string) $request->query('phone', ''));

        $payments = collect();
        if ($phone !== '') {
            $payments = HospitalPayment::where('patient_phone', $phone)
                ->where('status', 'completed')
                ->latest('payment_date')
                ->limit(10)
                ->get();
        }

        return view('hospital-payment.history', [
            'payments' => $payments,
            'phone'    => $phone,
        ]);
    }
}
