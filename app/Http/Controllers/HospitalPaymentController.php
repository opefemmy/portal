<?php

namespace App\Http\Controllers;

use App\Models\Hospital\HospitalServiceType;
use App\Models\Hospital\HospitalPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        ]);

        // Get service details
        $service = HospitalServiceType::findOrFail($request->service_type_id);

        // Calculate portal charge (2%)
        $portalCharge = ($service->amount * 2) / 100;
        $totalAmount = $service->amount + $portalCharge;

        // Generate unique payment reference
        $paymentRef = 'HSP-' . strtoupper(Str::random(10));

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

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully!',
            'payment_id' => $payment->id,
            'reference' => $paymentRef,
            'receipt_url' => route('hospital-payment.receipt', $payment->id),
        ]);
    }

    /**
     * Validate payment by reference
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

        return response()->json([
            'success' => true,
            'payment' => [
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
            ],
        ]);
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(HospitalPayment $payment)
    {
        return view('hospital-payment.receipt', compact('payment'));
    }
}
