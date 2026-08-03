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

        // Get results pending approval for these courses
        $results = Result::whereIn('course_id', $courseIds)
            ->where('status', 'pending_approval')
            ->with(['course', 'course.department', 'studentCourse.student.user', 'approvedBy'])
            ->latest()
            ->get();

        // Get recently approved results
        $approvedResults = Result::whereIn('course_id', $courseIds)
            ->where('status', 'approved')
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
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result approved');
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