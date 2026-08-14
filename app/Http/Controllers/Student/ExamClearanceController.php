<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Session;
use App\Models\Student;
use App\Services\SchoolFeeCalculator;
use Illuminate\Http\Request;

/**
 * Student-facing exam clearance.
 *
 * Index page shows a per-fee payment summary so the student can see how
 * close they are to 100% on each required fee. Print view produces the
 * official letterhead letter — it only renders once the student is
 * actually fully cleared, otherwise we redirect back with an error.
 */
class ExamClearanceController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('student.exam-clearance.view');
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $requiredFees = $this->requiredFeesFor($student, $currentSession);

        $perFeeStatus = $requiredFees->map(function (Fee $fee) use ($student) {
            $paid = SchoolFeeCalculator::totalPercentPaid($student, $fee);
            $payments = Payment::where('student_id', $student->id)
                ->where('fee_id', $fee->id)
                ->where('status', 'completed')
                ->orderBy('created_at')
                ->get();
            $category = $student->user?->isIndigene() ? 'indigene' : 'non_indigene';
            return [
                'fee'      => $fee,
                'paid'     => $paid,
                'payments' => $payments,
                'category' => $category,
                'price'    => $fee->priceFor($category),
                'portal'   => (float) $fee->portal_charge,
            ];
        });

        $fullyPaid = $perFeeStatus->every(fn ($row) => $row['paid'] >= SchoolFeeCalculator::PERCENT_FULL)
            && $perFeeStatus->count() > 0;

        return view('student.exam-clearance', [
            'student'       => $student,
            'perFeeStatus'  => $perFeeStatus,
            'fullyPaid'     => $fullyPaid,
            'session'       => $currentSession,
        ]);
    }

    public function print(Request $request)
    {
        $this->requirePermission('student.exam-clearance.view');
        $student = Student::where('user_id', $request->user()->id)->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $requiredFees = $this->requiredFeesFor($student, $currentSession);

        $perFeeStatus = $requiredFees->map(function (Fee $fee) use ($student) {
            $paid = SchoolFeeCalculator::totalPercentPaid($student, $fee);
            if ($paid < SchoolFeeCalculator::PERCENT_FULL) {
                // Hard-fail: refuse to print if any required fee is short.
                abort(redirect()
                    ->route('student.exam-clearance')
                    ->with('error', 'You are not yet cleared — ' . $fee->name . ' is at ' . $paid . '%.'));
            }
            $payments = Payment::where('student_id', $student->id)
                ->where('fee_id', $fee->id)
                ->where('status', 'completed')
                ->orderBy('created_at')
                ->get();
            $category = $student->user?->isIndigene() ? 'indigene' : 'non_indigene';
            return [
                'fee'      => $fee,
                'paid'     => $paid,
                'payments' => $payments,
                'category' => $category,
                'price'    => $fee->priceFor($category),
                'portal'   => (float) $fee->portal_charge,
            ];
        });

        return view('student.exam-clearance-print', [
            'student'      => $student,
            'perFeeStatus' => $perFeeStatus,
            'session'      => $currentSession,
        ]);
    }

    /**
     * Mirrors the fee scope used in Student\PaymentController::index —
     * same-session, matching school/department/programme, optional level.
     */
    private function requiredFeesFor(Student $student, ?Session $session)
    {
        return Fee::where('is_active', true)
            ->when($session, fn ($q) => $q->where('session_id', $session->id))
            ->where(function ($q) use ($student) {
                $q->where('school_id', $student->school_id)->orWhereNull('school_id');
            })
            ->where(function ($q) use ($student) {
                $q->where('department_id', $student->department_id)->orWhereNull('department_id');
            })
            ->where(function ($q) use ($student) {
                $q->where('level', $student->level)->orWhereNull('level');
            })
            ->orderBy('name')
            ->get();
    }
}