<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $sessionId = $request->session_id;
        $deptId = $request->department_id;

        // Get all active fees
        $fees = Fee::where('is_active', true)->orderBy('name')->get();

        // Get debtors - students who haven't paid required fees
        $debtors = [];
        $outstandingFees = Fee::where('is_active', true)
            ->where('session_id', $sessionId)
            ->when($deptId, function($q) use ($deptId) {
                $q->where('department_id', $deptId)->orWhereNull('department_id');
            })
            ->get();

        if ($outstandingFees->count() > 0) {
            $students = Student::with('user')
                ->when($deptId, function($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                })
                ->where('session_id', $sessionId)
                ->get();

            foreach ($students as $student) {
                $paidFees = Payment::where('student_id', $student->id)
                    ->where('status', 'completed')
                    ->pluck('fee_id')
                    ->toArray();

                $unpaidFees = $outstandingFees->whereNotIn('id', $paidFees);
                if ($unpaidFees->count() > 0) {
                    $debtors[] = [
                        'student' => $student,
                        'unpaid_fees' => $unpaidFees,
                        'total_unpaid' => $unpaidFees->sum('amount')
                    ];
                }
            }
        }

        $totalDebt = array_sum(array_column($debtors, 'total_unpaid'));

        return view('bursar.reports', compact('fees', 'debtors', 'totalDebt'));
    }
}