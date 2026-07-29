<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\StudentCourse;
use App\Models\Result;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // Get assignments with all required relationships
        $assignments = CourseAssignment::where('lecturer_id', $userId)
            ->with(['course', 'course.department', 'session', 'studentCourses', 'results'])
            ->get();

        // Pre-compute stats to avoid N+1 queries
        $totalStudents = 0;
        $pendingResults = 0;

        foreach ($assignments as $assignment) {
            $totalStudents += $assignment->studentCourses->count();
            $pendingResults += $assignment->results->where('status', 'pending_approval')->count();
        }

        // Pass pre-computed stats to view
        $stats = [
            'total_courses' => $assignments->count(),
            'total_students' => $totalStudents,
            'pending_results' => $pendingResults,
        ];

        return view('lecturer.dashboard', compact('assignments', 'stats'));
    }

    public function courses()
    {
        $assignments = CourseAssignment::where('lecturer_id', auth()->id())
            ->with(['course', 'course.department', 'session'])
            ->get();
        return view('lecturer.courses', compact('assignments'));
    }

    public function courseStudents(Course $course)
    {
        return view('lecturer.course-students');
    }

    public function timetable()
    {
        return view('lecturer.timetable');
    }
}