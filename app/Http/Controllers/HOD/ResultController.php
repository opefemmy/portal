<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Result;
use App\Models\Course;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('academic.results.view');

        $user = auth()->user();
        $departmentId = $user->department_id;

        if (!$departmentId) {
            return view('hod.results.index', [
                'results' => collect(),
                'approvedResults' => collect(),
            ]);
        }

        // Get courses in this department
        $courses = Course::where('department_id', $departmentId)->pluck('id');

        // Get results pending approval for these courses
        $results = Result::whereIn('course_id', $courses)
            ->where('status', 'pending_approval')
            ->with(['course', 'studentCourse.student.user', 'approvedBy'])
            ->latest()
            ->get();

        // Get recently approved results
        $approvedResults = Result::whereIn('course_id', $courses)
            ->where('status', 'approved')
            ->with(['course', 'studentCourse.student.user', 'approvedBy'])
            ->latest()
            ->limit(20)
            ->get();

        return view('hod.results.index', compact('results', 'approvedResults'));
    }

    public function approve(Result $result, Request $request)
    {
        $this->requirePermission('academic.results.approve');
        $this->assertInHodDepartment($result);
        $this->assertCanActOn($result, 'pending_approval');
        $result->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result approved at HOD stage');
    }

    public function reject(Result $result, Request $request)
    {
        $this->requirePermission('academic.results.approve');
        $this->assertInHodDepartment($result);
        $this->assertCanActOn($result, 'pending_approval');
        $result->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result rejected at HOD stage');
    }

    /**
     * Bulk approve results at the HOD stage. HOD sees every level in
     * their department (ND1/100L through HND2/400L) at once and can
     * tick the rows they want to push forward to the Dean.
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
        $courseIds = Course::where('department_id', $user->department_id)->pluck('id');

        $updated = Result::whereIn('id', $request->result_ids)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'pending_approval')
            ->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'remarks' => $request->remarks,
            ]);

        return back()->with('success', "{$updated} result(s) approved at HOD stage.");
    }

    /**
     * Bulk reject results at the HOD stage.
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
        $courseIds = Course::where('department_id', $user->department_id)->pluck('id');

        $updated = Result::whereIn('id', $request->result_ids)
            ->whereIn('course_id', $courseIds)
            ->where('status', 'pending_approval')
            ->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'remarks' => $request->remarks,
            ]);

        return back()->with('success', "{$updated} result(s) rejected at HOD stage.");
    }

    /**
     * Reject per-row action against a result that has already been
     * pushed to the next stage — Dean and beyond shouldn't be able
     * to silently undo HOD approval.
     */
    private function assertCanActOn(Result $result, string $expectedStatus): void
    {
        if ($result->status !== $expectedStatus) {
            abort(409, "This result is no longer in the {$expectedStatus} state and cannot be acted on at the HOD stage.");
        }
    }

    private function assertInHodDepartment(Result $result): void
    {
        $user = auth()->user();
        if (!$user || !$user->department_id) {
            abort(403, 'You are not assigned to a department.');
        }
        $course = $result->course ?? Course::find($result->course_id);
        if (!$course || (int) $course->department_id !== (int) $user->department_id) {
            abort(403, 'You are not allowed to act on this result.');
        }
    }
}