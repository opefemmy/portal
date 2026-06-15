<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Payment;
use App\Models\Session;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (!$student) {
            return view('student.dashboard', [
                'student' => null,
                'registeredCourses' => collect(),
                'payments' => collect(),
                'error' => 'Your student profile has not been set up yet. Please contact the registrar.'
            ]);
        }

        // Check if profile is incomplete
        $profileIncomplete = !$student->school_id || !$student->department_id || !$student->programme_id;

        $registeredCourses = StudentCourse::where('student_id', $student->id)
            ->with('course')
            ->get();

        $payments = Payment::where('student_id', $student->id)
            ->with('fee')
            ->latest()
            ->take(5)
            ->get();

        return view('student.dashboard', compact('student', 'registeredCourses', 'payments', 'profileIncomplete'));
    }
}