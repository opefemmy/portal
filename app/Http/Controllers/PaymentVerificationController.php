<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Hospital\HospitalPayment;
use App\Models\ExternalPayment;

class PaymentVerificationController extends Controller
{
    /**
     * Verify payment across all payment systems
     */
    public function verify($reference)
    {
        // Check Hospital Payments
        $hospitalPayment = HospitalPayment::where('payment_ref', $reference)->first();
        if ($hospitalPayment) {
            // Try to verify with gateway if pending
            if ($hospitalPayment->status === 'pending' && $hospitalPayment->payment_method === 'online') {
                // Trigger verification logic
                $hospitalPayment->refresh();
            }

            return response()->json([
                'success' => true,
                'type' => 'hospital',
                'status' => $hospitalPayment->status,
                'payment' => [
                    'id' => $hospitalPayment->id,
                    'reference' => $hospitalPayment->payment_ref,
                    'amount' => $hospitalPayment->total_amount,
                    'patient_name' => $hospitalPayment->patient_name,
                    'service_name' => $hospitalPayment->service_name,
                    'status' => $hospitalPayment->status,
                ],
            ]);
        }

        // Check Student Payments
        $studentPayment = Payment::where('reference', $reference)->orWhere('transaction_ref', $reference)->first();
        if ($studentPayment) {
            return response()->json([
                'success' => true,
                'type' => 'student',
                'status' => $studentPayment->status,
                'payment' => [
                    'id' => $studentPayment->id,
                    'reference' => $studentPayment->reference,
                    'amount' => $studentPayment->amount,
                    'status' => $studentPayment->status,
                ],
            ]);
        }

        // Check External Payments (Applicants)
        $externalPayment = ExternalPayment::where('transaction_id', $reference)->first();
        if ($externalPayment) {
            return response()->json([
                'success' => true,
                'type' => 'external',
                'status' => $externalPayment->status,
                'payment' => [
                    'id' => $externalPayment->id,
                    'transaction_id' => $externalPayment->transaction_id,
                    'amount' => $externalPayment->amount,
                    'status' => $externalPayment->status,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment not found',
        ], 404);
    }
}
