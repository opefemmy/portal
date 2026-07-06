<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\CourseAssignment;
use App\Models\Timetable;
use App\Models\Session;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->first();

        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Please complete your profile first.');
        }

        $currentSession = Session::getCurrentSession();

        // Get courses the student is registered for
        $studentCourses = $student->studentCourses()
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with('course')
            ->get();

        // Get assigned courses for lecturers teaching these courses
        $lecturerCourses = CourseAssignment::where('department_id', $student->department_id)
            ->with('course', 'user')
            ->get();

        // Get timetable for the student's classes
        $timetables = Timetable::where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('session_id', $currentSession->id ?? 0)
            ->with('course')
            ->get();

        return view('student.attendance', compact('studentCourses', 'lecturerCourses', 'timetables', 'student'));
    }

    public function markAttendance(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
            'location' => 'required|string',
            'status' => 'required|in:present,absent,late',
        ]);

        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        // Verify location matches the class timetable
        $timetable = Timetable::where('course_id', $request->course_id)
            ->where('department_id', $student->department_id)
            ->where('level', $student->level)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('location', $request->location)
            ->first();

        if (!$timetable) {
            return back()->with('error', 'Invalid location for this course. Please check the timetable.');
        }

        // Check if attendance already marked
        $existing = StudentAttendance::where('student_id', $student->id)
            ->where('course_id', $request->course_id)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            return back()->with('error', 'Attendance already marked for this date.');
        }

        StudentAttendance::create([
            'student_id' => $student->id,
            'course_id' => $request->course_id,
            'session_id' => $currentSession->id,
            'semester' => 'first',
            'date' => $request->date,
            'status' => $request->status,
            'location' => $request->location,
            'notes' => $request->notes ?? null,
        ]);

        return back()->with('success', 'Attendance marked successfully!');
    }

    public function myAttendance()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $currentSession = Session::getCurrentSession();

        $attendances = StudentAttendance::where('student_id', $student->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->with('course')
            ->orderByDesc('date')
            ->paginate(30);

        // Calculate attendance percentage
        $totalClasses = $attendances->count();
        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $attendancePercentage = $totalClasses > 0 ? (($presentCount + $lateCount) / $totalClasses) * 100 : 0;

        return view('student.my-attendance', compact('attendances', 'attendancePercentage', 'student'));
    }
}