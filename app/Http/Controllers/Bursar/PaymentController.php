<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('student.user', 'fee')->latest()->get();
        return view('bursar.payments', compact('payments'));
    }

    public function verify(Payment $payment)
    {
        $payment->update(['status' => 'completed']);
        return back()->with('success', 'Payment verified');
    }
}