<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    use EnforcesPermission;

    public function index(\App\Models\Course $course)
    {
        $this->requirePermission('academic.attendance.view');
        return view('lecturer.attendance', compact('course'));
    }

    public function mark(Request $request, \App\Models\Course $course)
    {
        $this->requirePermission('academic.attendance.mark');
        return back()->with('success', 'Attendance marked');
    }

    public function report(\App\Models\Course $course)
    {
        $this->requirePermission('academic.attendance.view');
        return view('lecturer.attendance-report', compact('course'));
    }
}