<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Payment;
use App\Models\Fee;
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
                'fees' => collect(),
                'unpaidFees' => collect(),
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

        // Get fees based on student's department, programme, and session
        $fees = Fee::where('session_id', $student->session_id)
            ->where(function($query) use ($student) {
                $query->where('department_id', $student->department_id)
                    ->orWhereNull('department_id');
            })
            ->where(function($query) use ($student) {
                $query->where('programme_id', $student->programme_id)
                    ->orWhereNull('programme_id');
            })
            ->where('is_active', true)
            ->orderBy('amount')
            ->get();

        // Get unpaid fees
        $paidFeeIds = Payment::where('student_id', $student->id)
            ->where('status', 'completed')
            ->pluck('fee_id')
            ->toArray();

        $unpaidFees = $fees->whereNotIn('id', $paidFeeIds);

        return view('student.dashboard', compact('student', 'registeredCourses', 'payments', 'fees', 'unpaidFees', 'profileIncomplete'));
    }
}