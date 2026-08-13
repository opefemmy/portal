<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Student;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use EnforcesPermission;

    /**
     * Bursary reports page. Totals on this page must reconcile with
     * /bursar/payments (PaymentController) and /bursar/paid-students
     * (DashboardController::paidStudents). To make that true we:
     *
     *   1. Use status IN ('completed', 'verified') — verify() writes
     *      'completed'; some legacy seed rows are 'verified'.
     *   2. Sum amounts directly from the payments table (same query the
     *      other two screens use), not from the Fee catalogue.
     */
    public function index(Request $request)
    {
        $this->requirePermission('bursar.reports.view');

        // Default to the currently-active session when none is picked so the
        // page is meaningful on first load.
        $sessionId = $request->session_id;
        $deptId = $request->department_id;

        if (!$sessionId) {
            $current = Session::getCurrentSession();
            $sessionId = $current?->id;
        }

        // Get all active fees (used by the upload/sync UI + filter dropdowns)
        $fees = Fee::where('is_active', true)->orderBy('name')->get();
        $sessions = Session::orderByDesc('name')->get();

        // Both statuses count as "paid" — see note at top of file.
        $paidStatuses = ['completed', 'verified'];

        // Aggregate payments under the same filters the rest of bursar
        // screens use, so the totals reconcile.
        $paymentsBase = Payment::query()
            ->whereIn('status', $paidStatuses)
            ->whereHas('student', function ($q) use ($sessionId, $deptId) {
                if ($sessionId) {
                    $q->where('session_id', $sessionId);
                }
                if ($deptId) {
                    $q->where('department_id', $deptId);
                }
            });

        $summary = [
            'total_paid'        => (float) (clone $paymentsBase)->sum('amount'),
            'payment_count'     => (clone $paymentsBase)->count(),
            'paid_students'     => (clone $paymentsBase)->whereNotNull('student_id')->distinct('student_id')->count('student_id'),
            'total_expected'    => (float) Fee::where('is_active', true)
                ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
                ->when($deptId, fn($q) => $q->where('department_id', $deptId)->orWhereNull('department_id'))
                ->sum('amount'),
        ];
        $summary['outstanding'] = max(0, $summary['total_expected'] - $summary['total_paid']);

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
                ->when($sessionId, function($q) use ($sessionId) {
                    $q->where('session_id', $sessionId);
                })
                ->get();

            foreach ($students as $student) {
                // Match the same paid-status rule everywhere in bursar.
                $paidFees = Payment::where('student_id', $student->id)
                    ->whereIn('status', $paidStatuses)
                    ->pluck('fee_id')
                    ->toArray();

                $unpaidFees = $outstandingFees->whereNotIn('id', $paidFees);
                if ($unpaidFees->count() > 0) {
                    $debtors[] = [
                        'student' => $student,
                        'unpaid_fees' => $unpaidFees,
                        'total_unpaid' => $unpaidFees->sum('amount'),
                    ];
                }
            }
        }

        $totalDebt = array_sum(array_column($debtors, 'total_unpaid'));

        return view('bursar.reports', compact(
            'fees', 'sessions', 'debtors', 'totalDebt', 'summary', 'sessionId', 'deptId'
        ));
    }
}