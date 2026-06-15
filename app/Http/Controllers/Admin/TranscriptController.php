<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Result;
use App\Models\Session;
use Illuminate\Http\Request;

class TranscriptController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'department', 'programme']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('matric_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($uq) use ($request) {
                      $uq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        $students = $query->paginate(20);
        return view('admin.transcripts.index', compact('students'));
    }

    public function show(Student $student)
    {
        $results = Result::whereHas('studentCourse', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })->with(['studentCourse.course'])->get();

        $sessions = Session::all();
        $cgpa = $student->calculateCGPA();

        return view('admin.transcripts.show', compact('student', 'results', 'sessions', 'cgpa'));
    }

    public function print(Student $student)
    {
        $results = Result::whereHas('studentCourse', function($q) use ($student) {
            $q->where('student_id', $student->id);
        })->with(['studentCourse.course', 'studentCourse.session'])->get();

        $cgpa = $student->calculateCGPA();

        return view('admin.transcripts.print', compact('student', 'results', 'cgpa'));
    }
}