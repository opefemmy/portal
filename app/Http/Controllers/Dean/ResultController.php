<?php

namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index()
    {
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
        $this->assertInDeansSchool($result);
        $result->update([
            'status' => 'approved_by_dean',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result approved at Dean stage');
    }

    /**
     * Bulk approve results at the Dean stage.
     */
    public function bulkApprove(Request $request)
    {
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
}