<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Course;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index()
    {
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
        $this->assertInHodDepartment($result);
        $result->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result approved');
    }

    public function reject(Result $result, Request $request)
    {
        $this->assertInHodDepartment($result);
        $result->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks,
        ]);
        return back()->with('success', 'Result rejected');
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