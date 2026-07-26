<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OnlinePaymentController extends Controller
{
    /**
     * Lookup student by matric number, phone, or registration number
     */
    public function lookupStudent(Request $request)
    {
        $request->validate([
            'payer_id' => 'required|string|min:3',
        ]);

        $payerId = trim($request->payer_id);

        // Try to find student by different identifiers
        $student = null;

        // 1. Try by matric number
        $student = Student::where('matric_number', 'like', '%' . $payerId . '%')
            ->with('user')
            ->first();

        // 2. Try by phone number
        if (!$student) {
            $student = Student::whereHas('user', function ($q) use ($payerId) {
                $q->where('phone', 'like', '%' . $payerId . '%');
            })->with('user')->first();
        }

        // 3. Try by registration number (if different from matric)
        if (!$student) {
            $student = Student::where('registration_number', 'like', '%' . $payerId . '%')
                ->with('user')
                ->first();
        }

        // 4. Try by email
        if (!$student) {
            $student = Student::whereHas('user', function ($q) use ($payerId) {
                $q->where('email', 'like', '%' . $payerId . '%');
            })->with('user')->first();
        }

        if ($student && $student->user) {
            return response()->json([
                'success' => true,
                'student_id' => $student->id,
                'name' => $student->user->name,
                'email' => $student->user->email,
                'phone' => $student->user->phone ?? '',
                'matric_number' => $student->matric_number,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Student not found. Please check your input and try again.',
        ]);
    }

    /**
     * Process online payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'payment_type_id' => 'required|exists:payment_types,id',
            'student_id' => 'nullable|exists:students,id',
            'payer_id' => 'required|string',
            'payer_name' => 'required|string',
            'payer_email' => 'required|email',
            'payer_phone' => 'required|string',
            'payment_purpose' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'portal_charge' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:online,bank_transfer',
        ]);

        // Get payment type details
        $paymentType = PaymentType::findOrFail($request->payment_type_id);

        // Generate unique payment reference
        $paymentRef = 'ONL-' . strtoupper(Str::random(10));

        // Find or create student
        $student = null;
        if ($request->student_id) {
            $student = Student::find($request->student_id);
        }

        // Create payment record
        $payment = Payment::create([
            'student_id' => $student?->id,
            'fee_id' => null, // No fee linked for PaymentType payments
            'amount' => $request->amount,
            'portal_charge' => $request->portal_charge ?? 0,
            'status' => 'pending',
            'payment_ref' => $paymentRef,
            'payment_method' => $request->payment_method,
            'payment_date' => now(),
            'payer_name' => $request->payer_name,
            'payer_email' => $request->payer_email,
            'payer_phone' => $request->payer_phone,
            'payer_id' => $request->payer_id,
            'payment_purpose' => $paymentType->name,
        ]);

        // In production, you would integrate with payment gateway here
        // For now, we'll mark as pending and provide receipt

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully!',
            'payment_id' => $payment->id,
            'reference' => $paymentRef,
            'receipt_url' => route('online-payment.receipt', $payment->id),
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

        $payment = Payment::where('payment_ref', $request->payment_reference)
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
                'payer_name' => $payment->payer_name,
                'payer_email' => $payment->payer_email,
                'payer_phone' => $payment->payer_phone,
                'amount' => $payment->amount,
                'portal_charge' => $payment->portal_charge,
                'total_amount' => $payment->total_amount,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'payment_purpose' => $payment->payment_purpose,
                'created_at' => $payment->created_at->format('d M Y, h:i A'),
            ],
        ]);
    }

    /**
     * Print payment receipt
     */
    public function printReceipt(Payment $payment)
    {
        return view('online-payment.receipt', compact('payment'));
    }
}
