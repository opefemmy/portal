<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Result;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }

    public function students(Request $request)
    {
        $query = Student::with('user', 'department', 'school', 'programme');

        if ($request->school_id) {
            $query->where('school_id', $request->school_id);
        }
        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->level) {
            $query->where('level', $request->level);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $students = $query->latest()->get();
        return view('admin.reports.students', compact('students'));
    }

    public function results(Request $request)
    {
        $results = Result::with('studentCourse.student.user', 'studentCourse.course')
            ->latest()
            ->take(100)
            ->get();
        return view('admin.reports.results', compact('results'));
    }

    public function payments(Request $request)
    {
        $query = Payment::with('student.user', 'fee');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->from_date) {
            $query->where('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('created_at', '<=', $request->to_date);
        }

        $payments = $query->latest()->get();
        return view('admin.reports.payments', compact('payments'));
    }
}