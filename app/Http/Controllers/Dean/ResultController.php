<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Result;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('academic.results.view');

        $user = auth()->user();
        $schoolId = $user->school_id;

        if (!$schoolId) {
            return view('dean.results.index', [
                'results' => collect(),
                'approvedResults' => collect(),
            ]);
        }

        // Get all departments in this school
        $departmentIds = Department::where('school_id', $schoolId)->pluck('id');

        // Get all courses in these departments
        $courseIds = Course::whereIn('department_id', $departmentIds)->pluck('id');

        // Get results pending approval for these courses.
        // The HOD writes 'approved' (not 'pending_approval') when they
        // sign off, so the Dean's queue reads 'approved' rows from the
        // HOD stage of the pipeline.
        $results = Result::whereIn('course_id', $courseIds)
            ->where('status', 'approved')
            ->with(['course', 'course.department', 'studentCourse.student.user', 'approvedBy'])
            ->latest()
            ->get();

        // Get recently approved results
        $approvedResults = Result::whereIn('course_id', $courseIds)
            ->where('status', 'approved_by_dean')
            ->with(['course', 'course.department', 'studentCourse.student.user', 'approvedBy'])
            ->latest()
            ->limit(20)
            ->get();

        return view('dean.results.index', compact('results', 'approvedResults'));
    }

    public function approve(Result $result, Request $request)
    {
        $this->requirePermission('academic.results.approve');
        $this->assertInDeansSchool($result);
        $this->assertCanActOn($result, 'approved');
        $result->update([
            'status' => 'approved_by_dean',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result approved at Dean stage');
    }

    /**
     * Per-row reject at the Dean stage. Same pattern as HOD's
     * `reject` — pulled out so a Dean can drop a single result
     * (e.g. wrong school) without bulk-rejecting the whole batch.
     */
    public function reject(Result $result, Request $request)
    {
        $this->requirePermission('academic.results.reject');
        $this->assertInDeansSchool($result);
        $this->assertCanActOn($result, 'approved');
        $result->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result rejected at Dean stage');
    }

    /**
     * Printable Dean-stage signing page. Mirrors the Academic Board
     * signing page but pulls only results the Dean has signed off
     * (`status = 'approved_by_dean'`) so the signature block lines
     * up with what this Dean personally approved.
     */
    public function signingPage(Request $request)
    {
        $this->requirePermission('academic.results.view');

        $user = auth()->user();
        $schoolId = $user?->school_id;
        if (!$schoolId) {
            return view('dean.results.signing-page', [
                'results' => collect(),
                'schools' => collect(),
                'departments' => collect(),
                'programmes' => collect(),
                'sessions' => collect(),
                'filters' => [],
            ]);
        }

        $departmentIds = Department::where('school_id', $schoolId)->pluck('id');
        $courseIds = Course::whereIn('department_id', $departmentIds)->pluck('id');

        $query = Result::whereIn('course_id', $courseIds)
            ->where('status', 'approved_by_dean')
            ->with(['course.department', 'course.school', 'studentCourse.student.user', 'studentCourse.session', 'approvedBy']);

        $filters = [];

        if ($deptId = $request->integer('department_id')) {
            if ($departmentIds->contains($deptId)) {
                $courseIdsInDept = Course::where('department_id', $deptId)->pluck('id');
                $query->whereIn('course_id', $courseIdsInDept);
                $filters['department_id'] = $deptId;
            }
        }

        if ($progId = $request->integer('programme_id')) {
            $query->whereHas('studentCourse.student', function ($q) use ($progId) {
                $q->where('programme_id', $progId);
            });
            $filters['programme_id'] = $progId;
        }

        if ($sessionId = $request->integer('session_id')) {
            $query->whereHas('studentCourse', function ($q) use ($sessionId) {
                $q->where('session_id', $sessionId);
            });
            $filters['session_id'] = $sessionId;
        }

        $results = $query->orderByDesc('approved_at')->paginate(50)->withQueryString();

        $departments = Department::where('school_id', $schoolId)->orderBy('name')->pluck('name', 'id');
        $programmes = \App\Models\Programme::whereIn('department_id', $departmentIds)->orderBy('name')->pluck('name', 'id');
        $sessions = \App\Models\Session::orderByDesc('name')->pluck('name', 'id');

        return view('dean.results.signing-page', [
            'results' => $results,
            'departments' => $departments,
            'programmes' => $programmes,
            'sessions' => $sessions,
            'filters' => $filters,
        ]);
    }

    /**
     * Bulk approve results at the Dean stage.
     */
    public function bulkApprove(Request $request)
    {
        $this->requirePermission('academic.results.approve');

        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'integer|exists:results,id',
            'remarks' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $departmentIds = Department::where('school_id', $user->school_id)->pluck('id');
        $courseIds = Course::whereIn('department_id', $departmentIds)->pluck('id');

        $updated = Result::whereIn('id', $request->result_ids)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'approved')
            ->update([
                'status' => 'approved_by_dean',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'remarks' => $request->remarks,
            ]);

        return back()->with('success', "{$updated} result(s) approved at Dean stage.");
    }

    /**
     * Bulk reject results at the Dean stage.
     */
    public function bulkReject(Request $request)
    {
        $this->requirePermission('academic.results.approve');

        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'integer|exists:results,id',
            'remarks' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $departmentIds = Department::where('school_id', $user->school_id)->pluck('id');
        $courseIds = Course::whereIn('department_id', $departmentIds)->pluck('id');

        $updated = Result::whereIn('id', $request->result_ids)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'approved')
            ->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'remarks' => $request->remarks,
            ]);

        return back()->with('success', "{$updated} result(s) rejected at Dean stage.");
    }

    private function assertInDeansSchool(Result $result): void
    {
        $user = auth()->user();
        if (!$user || !$user->school_id) {
            abort(403, 'You are not assigned to a school.');
        }
        $course = $result->course ?? Course::with('department')->find($result->course_id);
        $departmentSchoolId = $course && $course->department
            ? $course->department->school_id
            : null;
        if (!$departmentSchoolId || (int) $departmentSchoolId !== (int) $user->school_id) {
            abort(403, 'You are not allowed to act on this result.');
        }
    }

    /**
     * Stop the Dean from re-acting on a result that has already
     * moved past the Dean stage. Mirrors `HOD\ResultController`'s
     * guard.
     */
    private function assertCanActOn(Result $result, string $expectedStatus): void
    {
        if ($result->status !== $expectedStatus) {
            abort(409, "This result is no longer in the {$expectedStatus} state and cannot be acted on at the Dean stage.");
        }
    }
}