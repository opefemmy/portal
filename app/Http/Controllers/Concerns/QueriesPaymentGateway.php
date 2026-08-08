<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\XpressPaymentsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for re-checking a payment row against the
 * gateway that initiated it.
 *
 * Used by:
 *   - `Student\PaymentController::verifyPaystackPayment` /
 *     `verifyFlutterwavePayment` (the original callback path)
 *   - `Payment\PaymentRequeryController::requery` (the new "user
 *     pressed Requery on a stuck row" path)
 *
 * Returns a normalised shape so the caller doesn't need to branch on
 * gateway-specific JSON envelopes:
 *
 *     [
 *         'success' => bool,
 *         'transaction_id' => ?string,
 *         'amount_kobo' => ?int,
 *         'currency' => ?string,
 *         'message' => ?string,
 *     ]
 *
 * `success=true` means the gateway confirmed the payment. The caller
 * is responsible for updating the Payment row + applying audience-side
 * side effects (e.g. ApplicantPaymentService::markCompleted for
 * applicant-side rows). We deliberately don't mutate the row from
 * inside this trait — the callback and requery paths have slightly
 * different side-effect choreography (the callback also runs fee-
 * specific activation, the requery path is read-only).
 */
trait QueriesPaymentGateway
{
    /**
     * Resolve the gateway row whose `provider` matches the payment's
     * stored gateway value. Returns null when the gateway isn't
     * configured — the caller treats that as "can't requery".
     */
    protected function resolveGatewayForPayment(Payment $payment): ?PaymentGateway
    {
        return PaymentGateway::where('provider', $payment->gateway)->first();
    }

    /**
     * Hit the gateway's verify endpoint and return a normalised result.
     *
     * Why \Throwable and not \Exception: Paystack / Flutterwave can
     * return empty bodies under PHP 8; the existing verify helpers
     * catch \Throwable for the same reason (see
     * `Student\PaymentController::verifyPaystackPayment`).
     */
    protected function verifyWithGateway(Payment $payment): array
    {
        try {
            return match ($payment->gateway) {
                PaymentGateway::PROVIDER_PAYSTACK       => $this->verifyPaystack($payment),
                PaymentGateway::PROVIDER_FLUTTERWAVE    => $this->verifyFlutterwave($payment),
                PaymentGateway::PROVIDER_XPRESSPAYMENTS => $this->verifyXpress($payment),
                default => [
                    'success' => false,
                    'message' => "Gateway [{$payment->gateway}] cannot be requeried online.",
                ],
            };
        } catch (\Throwable $e) {
            Log::warning('payment requery: gateway call threw', [
                'payment_id' => $payment->id,
                'gateway'    => $payment->gateway,
                'error'      => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'message' => 'Could not reach the gateway. Please try again later.',
            ];
        }
    }

    /**
     * Paystack: `GET /transaction/verify/{reference}`.
     * Same null-safe shape as the existing callback handler.
     */
    private function verifyPaystack(Payment $payment): array
    {
        $gateway = $this->resolveGatewayForPayment($payment);
        if (! $gateway) {
            return ['success' => false, 'message' => 'Paystack gateway not configured.'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $gateway->getSecretKey(),
        ])->get('https://api.paystack.co/transaction/verify/' . $payment->reference);

        $result = json_decode($response->body());

        // Same null-safe guard as the existing callback — empty /
        // non-JSON bodies throw \Error under PHP 8 if we read
        // ->status without checking.
        if (is_object($result) && !empty($result->status)
            && ($result->data->status ?? null) === 'success') {
            return [
                'success'        => true,
                'transaction_id' => $result->data->transaction_id ?? null,
                'amount_kobo'    => $result->data->amount ?? null,
                'currency'       => $result->data->currency ?? null,
            ];
        }

        $reason = is_object($result) ? ($result->message ?? 'verification failed') : 'verification failed';
        return [
            'success' => false,
            'message' => 'Paystack: ' . $reason,
        ];
    }

    /**
     * Flutterwave: `GET /v3/transactions/verify_by_ref?tx_ref=…`.
     */
    private function verifyFlutterwave(Payment $payment): array
    {
        $gateway = $this->resolveGatewayForPayment($payment);
        if (! $gateway) {
            return ['success' => false, 'message' => 'Flutterwave gateway not configured.'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $gateway->getSecretKey(),
        ])->get('https://api.flutterwave.com/v3/transactions/verify_by_ref', [
            'tx_ref' => $payment->reference,
        ]);

        $result = json_decode($response->body());

        if (is_object($result) && ($result->status ?? null) === 'success'
            && ($result->data->status ?? null) === 'successful') {
            return [
                'success'        => true,
                'transaction_id' => $result->data->id ?? null,
                'amount_kobo'    => isset($result->data->amount) ? (int) ($result->data->amount * 100) : null,
                'currency'       => $result->data->currency ?? null,
            ];
        }

        $reason = is_object($result) ? ($result->message ?? 'verification failed') : 'verification failed';
        return [
            'success' => false,
            'message' => 'Flutterwave: ' . $reason,
        ];
    }

    /**
     * XpressPayments: service handles its own row mutation + exception
     * recovery. We re-read the row afterwards to know whether the
     * service settled it.
     */
    private function verifyXpress(Payment $payment): array
    {
        $service = app(XpressPaymentsService::class);
        if (! $service->isConfigured()) {
            return ['success' => false, 'message' => 'XpressPayments gateway not configured.'];
        }

        // XpressPaymentsService::verifyPayment() looks up by
        // transaction_ref. Fall back to the canonical reference if the
        // row doesn't have one stored (the online-payment path writes
        // both, but be defensive).
        $transactionRef = $payment->transaction_id ?: $payment->reference;

        try {
            $service->verifyPayment($transactionRef);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'XpressPayments: ' . $e->getMessage(),
            ];
        }

        $fresh = $payment->fresh();
        if ($fresh && in_array($fresh->status, ['paid', 'verified', 'completed'], true)) {
            return [
                'success'        => true,
                'transaction_id' => $fresh->transaction_id,
                'amount_kobo'    => null,
                'currency'       => null,
            ];
        }

        return [
            'success' => false,
            'message' => 'XpressPayments: payment is not yet settled.',
        ];
    }
}