<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $schools = School::all();
        $departments = Department::all();
        $programmes = Programme::all();
        $sessions = Session::where('is_current', true)->get();

        return view('student.profile', compact('student', 'schools', 'departments', 'programmes', 'sessions'));
    }

    public function update(Request $request)
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'matric_number' => 'nullable|string|max:50',
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'level' => 'required|integer|min:1|max:6',
        ]);

        $student->update($validated);

        return redirect()->route('student.dashboard')->with('success', 'Profile updated successfully');
    }
}