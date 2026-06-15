<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(\App\Models\Course $course)
    {
        return view('lecturer.attendance', compact('course'));
    }

    public function mark(Request $request, \App\Models\Course $course)
    {
        return back()->with('success', 'Attendance marked');
    }

    public function report(\App\Models\Course $course)
    {
        return view('lecturer.attendance-report', compact('course'));
    }
}