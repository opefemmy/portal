<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $fees = Fee::where('session_id', setting('session_id'))
            ->where(function ($query) use ($student) {
                $query->where('school_id', $student->school_id)
                    ->orWhereNull('school_id');
            })->where(function ($query) use ($student) {
                $query->where('department_id', $student->department_id)
                    ->orWhereNull('department_id');
            })->where(function ($query) use ($student) {
                $query->where('level', $student->level)
                    ->orWhereNull('level');
            })->get();

        $payments = Payment::where('student_id', $student->id)
            ->with('fee')
            ->latest()
            ->get();

        return view('student.payments', compact('fees', 'payments'));
    }

    public function pay(Fee $fee)
    {
        return view('student.payment-pay', compact('fee'));
    }

    public function initiatePayment(Request $request, Fee $fee)
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        $payment = Payment::create([
            'student_id' => $student->id,
            'fee_id' => $fee->id,
            'amount' => $fee->amount,
            'reference' => Payment::generateReference(),
            'gateway' => 'paystack',
            'status' => 'pending',
        ]);

        return redirect()->route('student.payments.verify', ['reference' => $payment->reference])
            ->with('success', 'Payment initiated');
    }

    public function verifyPayment(Request $request)
    {
        return view('student.payment-verify');
    }

    public function printReceipt(Payment $payment)
    {
        return view('student.payment-receipt', compact('payment'));
    }
}