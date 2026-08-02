<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\Result;
use App\Models\Student;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $departmentId = $user->department_id;

        $stats = [
            'total_courses' => 0,
            'total_assignments' => 0,
            'total_lecturers' => 0,
            'total_students' => 0,
            'pending_results' => 0,
        ];

        $recentAssignments = collect();
        $pendingResultsList = collect();

        if ($departmentId) {
            $courseIds = Course::where('department_id', $departmentId)->pluck('id');

            $stats['total_courses'] = $courseIds->count();
            $stats['total_assignments'] = CourseAssignment::whereIn('course_id', $courseIds)->count();

            // Count lecturers assigned to courses in this department
            $stats['total_lecturers'] = CourseAssignment::whereIn('course_id', $courseIds)
                ->whereNotNull('lecturer_id')
                ->distinct('lecturer_id')
                ->count('lecturer_id');

            // Count students in this department
            $stats['total_students'] = Student::where('department_id', $departmentId)->count();

            // Pending results
            $stats['pending_results'] = Result::whereIn('course_id', $courseIds)
                ->where('status', 'pending_approval')
                ->count();

            $recentAssignments = CourseAssignment::whereIn('course_id', $courseIds)
                ->with(['course', 'lecturer', 'session'])
                ->latest()
                ->limit(5)
                ->get();

            $pendingResultsList = Result::whereIn('course_id', $courseIds)
                ->where('status', 'pending_approval')
                ->with(['course', 'studentCourse.student'])
                ->latest()
                ->limit(5)
                ->get();
        }

        return view('hod.dashboard', compact('stats', 'recentAssignments', 'pendingResultsList', 'departmentId'));
    }
}
