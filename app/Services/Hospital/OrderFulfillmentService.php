<?php

namespace App\Services\Hospital;

use App\Models\Hospital\HospitalOrderItem;
use App\Models\Hospital\HospitalPayment;
use Illuminate\Support\Facades\Log;

/**
 * OrderFulfillmentService
 *
 * Marks HospitalOrderItem rows as paid once the linked HospitalPayment
 * is marked completed by the payment gateway. Called from
 * HospitalPaymentController::verifyWithGateway() alongside the existing
 * auto-dispense and notification hooks.
 */
class OrderFulfillmentService
{
    /**
     * Mark every order item that points at this payment as paid.
     * Pharmacy and lab queues then surface them.
     */
    public function fulfillOnPayment(HospitalPayment $payment): void
    {
        $items = HospitalOrderItem::where('payment_id', $payment->id)
            ->where('status', HospitalOrderItem::STATUS_AWAITING_PAYMENT)
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        foreach ($items as $item) {
            $item->update(['status' => HospitalOrderItem::STATUS_PAID]);
        }

        Log::info('Hospital order fulfilment', [
            'payment_id'   => $payment->id,
            'payment_ref'  => $payment->payment_ref,
            'item_count'   => $items->count(),
        ]);
    }
}