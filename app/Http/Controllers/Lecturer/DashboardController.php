<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $assignments = CourseAssignment::where('lecturer_id', auth()->id())
            ->with('course', 'session')
            ->get();
        return view('lecturer.dashboard', compact('assignments'));
    }

    public function courses()
    {
        $assignments = CourseAssignment::where('lecturer_id', auth()->id())
            ->with('course', 'session')
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