<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Session;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Paid statuses — the verify() flow writes 'completed'; some legacy
     * seed rows are 'verified'. Both count as paid so the totals tally
     * with /bursar/payments, /bursar/paid-students and /bursar/reports.
     */
    public const PAID_STATUSES = ['completed', 'verified'];

    public function index(Request $request)
    {
        $currentSession = Session::getCurrentSession();
        $schools = School::all();
        $paidStatuses = self::PAID_STATUSES;

        $totalExpected = Fee::where('session_id', $currentSession->id ?? 0)
            ->sum('amount');

        $totalPaid = Payment::whereHas('student', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0);
            })
            ->whereIn('status', $paidStatuses)
            ->sum('amount');

        $totalPending = $totalExpected - $totalPaid;

        // Single canonical debtor definition (see debtorQuery() below).
        // The dashboard tile, the /bursar/debtors list and the search
        // results all run through this helper so the count in the
        // header matches the dashboard tile and the badge.
        $debtorQuery = $this->debtorQuery($currentSession->id ?? null);

        $debtors = (clone $debtorQuery)
            ->with(['department', 'programme', 'user'])
            ->orderBy('matric_number')
            ->paginate(20);

        $paidStudents = Payment::whereIn('status', $paidStatuses)
            ->whereHas('student', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0);
            })
            ->with(['student.user', 'student.department', 'student.programme'])
            ->orderByDesc('created_at')
            ->paginate(20);

        // Filter by school if provided
        if ($request->has('school_id') && $request->school_id) {
            $debtors = $debtors->filter(function($student) use ($request) {
                return $student->school_id == $request->school_id;
            });
            $paidStudents = Payment::whereIn('status', $paidStatuses)
                ->whereHas('student', function($q) use ($currentSession, $request) {
                    $q->where('session_id', $currentSession->id ?? 0)
                      ->where('school_id', $request->school_id);
                })
                ->with(['student.user', 'student.department', 'student.programme'])
                ->orderByDesc('created_at')
                ->paginate(20);
        }

        $paymentStats = [
            'total_expected' => $totalExpected,
            'total_paid'     => $totalPaid,
            'total_pending'  => $totalPending,
            'debtors_count'  => (clone $debtorQuery)->count(),
            'paid_count'     => Payment::whereIn('status', $paidStatuses)
                ->whereHas('student', function($q) use ($currentSession) {
                    $q->where('session_id', $currentSession->id ?? 0);
                })->count(),
        ];

        return view('bursar.dashboard', compact(
            'debtors', 'paidStudents', 'paymentStats', 'schools', 'currentSession'
        ));
    }

    public function debtors(Request $request)
    {
        $currentSession = Session::getCurrentSession();

        // Use the same debtorQuery() the dashboard tile uses so the
        // number in the page header ( {{ $debtors->total() }} ) matches
        // the dashboard tile and the badge in the tabs.
        $query = $this->debtorQuery($currentSession->id ?? null)
            ->with(['department', 'programme', 'user']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('matric_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $debtors = $query->orderBy('matric_number')->paginate(50);

        return view('bursar.debtors', compact('debtors'));
    }

    public function paidStudents(Request $request)
    {
        $currentSession = Session::getCurrentSession();
        $paidStatuses = self::PAID_STATUSES;

        $query = Payment::whereIn('status', $paidStatuses)
            ->whereHas('student', function($q) use ($currentSession) {
                $q->where('session_id', $currentSession->id ?? 0);
            })
            ->with(['student.user', 'student.department', 'student.programme']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('matric_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $paidStudents = $query->orderByDesc('created_at')->paginate(50);

        // Total amount across all matching payments (full result set, not
        // just the current page) so the header reconciles with /bursar/payments.
        $totalAmount = (clone $query)->sum('amount');

        return view('bursar.paid-students', compact('paidStudents', 'totalAmount'));
    }

    /**
     * Canonical "who is a debtor?" query for the current session.
     *
     * A debtor is a student enrolled in the given session who has at least
     * one ACTIVE required fee for that session that is NOT covered by a
     * completed/verified payment. Methods that previously used the cruder
     * "no payment at all in this session" definition produced a smaller
     * set that did not reconcile with the /bursar/reports per-fee list —
     * a student who paid one fee but still owed another would appear in
     * reports but not in this list, and the dashboard tile badge would
     * not match the debtors page header.
     *
     * The result is the same Student Builder the views paginate, so the
     * page header shows `{{ $debtors->total() }}` and the dashboard tile
     * shows `(clone)->count()` from the same query.
     *
     * Implementation note: we use NOT EXISTS rather than NOT IN because
     * the payments table has nullable fee_id rows (legacy seed data),
     * and SQL's NOT IN with NULL on the right-hand side returns UNKNOWN
     * rather than TRUE — the PHP loop in ReportController uses
     * Collection::whereNotIn which treats null safely, so to keep the
     * two paths agreeing we mirror that with NOT EXISTS.
     *
     * @param  int|null  $sessionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function debtorQuery(?int $sessionId)
    {
        $paidStatuses = self::PAID_STATUSES;

        // Required fees for the session. If there are no active fees
        // configured, there are no debtors — return a query that always
        // matches zero rows.
        $requiredFeeIds = Fee::where('is_active', true)
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->pluck('id')
            ->all();

        if (empty($requiredFeeIds)) {
            // Match-nothing builder. Cheaper than wrapping in if/else at
            // every call site and paginates cleanly.
            return Student::query()->whereRaw('0 = 1');
        }

        $feeIdList = implode(',', array_map('intval', $requiredFeeIds));
        $statusList = implode(',', array_map(
            fn($s) => "'" . addslashes($s) . "'",
            $paidStatuses
        ));

        // Students enrolled in the session who have at least one required
        // fee for which no completed/verified payment exists.
        return Student::query()
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->whereExists(function ($sub) use ($feeIdList, $statusList) {
                $sub->selectRaw('1')
                    ->from('fees')
                    ->whereRaw("fees.id IN ({$feeIdList})")
                    ->whereNotExists(function ($paySub) use ($statusList) {
                        $paySub->selectRaw('1')
                            ->from('payments')
                            ->whereRaw('payments.student_id = students.id')
                            ->whereRaw("payments.status IN ({$statusList})")
                            ->whereRaw('payments.fee_id = fees.id');
                    });
            });
    }
}