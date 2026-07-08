<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\Session;
use App\Models\CarryOverCourse;
use App\Models\CourseClassification;
use Illuminate\Http\Request;

class CourseRegistrationController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $courses = StudentCourse::where('student_id', $student->id)
            ->with('course', 'session')
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
            return redirect()->route('student.profile')
                ->with('error', 'Please complete your profile to select department and programme.');
        }

        $currentSession = Session::getCurrentSession();

        // Get carry over courses from previous semesters
        $carryOverCourses = CarryOverCourse::where('student_id', $student->id)
            ->where('status', 'pending')
            ->with('course')
            ->get();

        // Get main courses for department, programme, and level
        $mainCourses = Course::where('school_id', $student->school_id)
            ->where('department_id', $student->department_id)
            ->where('programme_id', $student->programme_id)
            ->where('level', $student->level)
            ->whereDoesntHave('studentCourses', function($q) use ($student, $currentSession) {
                $q->where('student_id', $student->id)
                  ->where('session_id', $currentSession->id ?? 0);
            })
            ->get();

        // Get elective courses (courses with same level but different programme or marked as elective)
        $electiveCourses = Course::where('school_id', $student->school_id)
            ->where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('programme_id', '!=', $student->programme_id)
            ->orWhere(function($q) use ($student) {
                $q->where('department_id', $student->department_id)
                  ->where('level', $student->level)
                  ->whereHas('classification', function($q2) {
                      $q2->where('type', 'elective');
                  });
            })
            ->whereDoesntHave('studentCourses', function($q) use ($student, $currentSession) {
                $q->where('student_id', $student->id)
                  ->where('session_id', $currentSession->id ?? 0);
            })
            ->get();

        // Get already registered courses
        $registeredCourses = StudentCourse::where('student_id', $student->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->with('course')
            ->get();

        return view('student.courses-register', compact(
            'mainCourses', 'electiveCourses', 'carryOverCourses', 'registeredCourses', 'student'
        ));
    }

    public function storeRegistration(Request $request)
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id',
        ]);

        $courseTypes = $request->input('course_types', []);

        foreach ($request->courses as $courseId) {
            $type = $courseTypes[$courseId] ?? 'main';

            StudentCourse::firstOrCreate([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'session_id' => $currentSession->id,
            ], [
                'semester' => 'first',
                'status' => 'registered',
                'course_type' => $type,
            ]);

            // If carry over, update the carry over status
            if ($type === 'carry_over') {
                CarryOverCourse::where('student_id', $student->id)
                    ->where('course_id', $courseId)
                    ->update(['status' => 'registered']);
            }
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
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $courses = StudentCourse::where('student_id', $student->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with('course')
            ->get();

        return view('student.courses-print', compact('courses', 'student'));
    }
}