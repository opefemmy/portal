<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Result;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $results = Result::whereHas('studentCourse', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })->with('studentCourse.course')->get();

        return view('student.results', compact('results'));
    }

    public function show($semester)
    {
        return view('student.results-show');
    }

    public function printResult()
    {
        return view('student.results-print');
    }
}