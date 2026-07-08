<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Fee;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('student.user', 'fee');

        // Filter by status
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by fee type
        if ($request->fee_id) {
            $query->where('fee_id', $request->fee_id);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by student
        if ($request->matric_number) {
            $student = Student::where('matric_number', 'like', '%' . $request->matric_number . '%')->first();
            if ($student) {
                $query->where('student_id', $student->id);
            }
        }

        $payments = $query->latest()->get();
        $fees = Fee::where('is_active', true)->orderBy('name')->get();

        return view('bursar.payments', compact('payments', 'fees'));
    }

    public function verify(Payment $payment)
    {
        $payment->update(['status' => 'completed']);
        return back()->with('success', 'Payment verified');
    }

    public function receipt(Payment $payment)
    {
        return view('bursar.receipt', compact('payment'));
    }
}