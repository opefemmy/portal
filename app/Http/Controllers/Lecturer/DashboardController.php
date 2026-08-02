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

        try {
            // Get assignments with required relationships
            $assignments = CourseAssignment::where('lecturer_id', $userId)
                ->with(['course', 'course.department', 'session', 'studentCourses'])
                ->get();

            // Pre-compute stats safely
            $totalStudents = 0;
            $pendingResults = 0;

            foreach ($assignments as $assignment) {
                $totalStudents += $assignment->studentCourses->count() ?? 0;

                // Get results for this course safely
                $courseResults = Result::where('course_id', $assignment->course_id)
                    ->where('status', 'pending_approval')
                    ->count();
                $pendingResults += $courseResults;
            }

            $stats = [
                'total_courses' => $assignments->count(),
                'total_students' => $totalStudents,
                'pending_results' => $pendingResults,
            ];
        } catch (\Exception $e) {
            // If there's any error, use default values
            $assignments = collect([]);
            $stats = [
                'total_courses' => 0,
                'total_students' => 0,
                'pending_results' => 0,
            ];
        }

        return view('lecturer.dashboard', compact('assignments', 'stats'));
    }

    public function courses()
    {
        try {
            $assignments = CourseAssignment::where('lecturer_id', auth()->id())
                ->with(['course', 'course.department', 'session'])
                ->get();
            return view('lecturer.courses', compact('assignments'));
        } catch (\Exception $e) {
            \Log::error('Lecturer courses error: ' . $e->getMessage());
            return view('lecturer.courses', ['assignments' => collect()]);
        }
    }

    public function courseStudents(\App\Models\Course $course)
    {
        // Delegate to LecturerResultController which provides the data needed by the view.
        return app(\App\Http\Controllers\Lecturer\ResultController::class)->courseStudents($course);
    }

    public function timetable()
    {
        return view('lecturer.timetable');
    }
}