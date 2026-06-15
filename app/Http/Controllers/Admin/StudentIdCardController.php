<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentIdCardController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['user', 'department', 'programme', 'school']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('matric_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function($uq) use ($request) {
                      $uq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->level) {
            $query->where('level', $request->level);
        }

        $students = $query->latest()->paginate(20);
        $departments = \App\Models\Department::all();

        return view('admin.id-cards.index', compact('students', 'departments'));
    }

    public function generate(Student $student)
    {
        return view('admin.id-cards.generate', compact('student'));
    }

    public function print(Request $request)
    {
        $studentIds = $request->student_ids ?? [];
        $students = Student::with(['user', 'department', 'programme', 'school'])
            ->whereIn('id', $studentIds)
            ->get();

        return view('admin.id-cards.print', compact('students'));
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'nullable|integer|min:1|max:6',
        ]);

        $query = Student::with(['user', 'department', 'programme', 'school']);

        if ($request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->level) {
            $query->where('level', $request->level);
        }

        $students = $query->get();

        return view('admin.id-cards.print', compact('students'));
    }
}