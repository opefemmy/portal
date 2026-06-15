<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Session;
use Illuminate\Http\Request;

class CourseRegistrationController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $courses = StudentCourse::where('student_id', $student->id)
            ->with('course')
            ->get();
        return view('student.courses', compact('courses'));
    }

    public function register()
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Please complete your profile first.');
        }

        if (!$student->school_id || !$student->department_id || !$student->programme_id) {
            return redirect()->route('student.profile.edit')
                ->with('error', 'Please complete your profile to select department and programme.');
        }

        $availableCourses = Course::where('school_id', $student->school_id)
            ->where('department_id', $student->department_id)
            ->where('programme_id', $student->programme_id)
            ->where('level', $student->level)
            ->get();

        return view('student.courses-register', compact('availableCourses'));
    }

    public function storeRegistration(Request $request)
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
        ]);

        foreach ($request->courses as $courseId) {
            StudentCourse::firstOrCreate([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'session_id' => $currentSession->id,
            ], [
                'semester' => 'first',
                'status' => 'registered',
            ]);
        }

        return redirect()->route('student.courses')->with('success', 'Courses registered successfully!');
    }

    public function dropCourse(StudentCourse $studentCourse)
    {
        $studentCourse->update(['status' => 'dropped']);
        return back()->with('success', 'Course dropped successfully!');
    }

    public function printForm()
    {
        return view('student.courses-print');
    }
}