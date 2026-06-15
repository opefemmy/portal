<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\Session;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $timetables = Timetable::whereHas('courseAssignment.course', function ($query) use ($student) {
            $query->where('department_id', $student->department_id)
                ->where('level', $student->level);
        })->where('session_id', $currentSession->id)
            ->where('status', 'approved')
            ->with('courseAssignment.course', 'courseAssignment.lecturer')
            ->get();

        return view('student.timetable', compact('timetables'));
    }
}