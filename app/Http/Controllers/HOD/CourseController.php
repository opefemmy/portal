<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\User;
use App\Models\Role;
use App\Models\Session;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get courses for HOD's department
        $courses = Course::where('department_id', $user->department_id)->get();

        // Get all course assignments for HOD's department
        $assignments = CourseAssignment::whereHas('course', function($query) use ($user) {
            $query->where('department_id', $user->department_id);
        })->with(['course', 'lecturer', 'session'])->get();

        return view('hod.courses', compact('courses', 'assignments'));
    }

    public function assign()
    {
        $user = auth()->user();

        // Get courses for HOD's department that don't have lecturer assigned
        $courses = Course::where('department_id', $user->department_id)->get();

        // Get lecturers - find by role slug
        $lecturerRole = Role::where('slug', 'lecturer')->first();
        $lecturers = [];
        if ($lecturerRole) {
            $lecturers = User::where('role_id', $lecturerRole->id)->get();
        }

        // Get existing assignments
        $assignments = CourseAssignment::whereHas('course', function($query) use ($user) {
            $query->where('department_id', $user->department_id);
        })->with(['course', 'lecturer', 'session'])->get();

        // Get current session
        $currentSession = Session::where('is_current', true)->first();

        return view('hod.courses-assign', compact('courses', 'lecturers', 'assignments', 'currentSession'));
    }

    public function storeAssignment(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lecturer_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:sessions,id',
        ]);

        // Check if already assigned
        $exists = CourseAssignment::where('course_id', $request->course_id)
            ->where('session_id', $request->session_id)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This course is already assigned for this session');
        }

        CourseAssignment::create([
            'course_id' => $request->course_id,
            'lecturer_id' => $request->lecturer_id,
            'session_id' => $request->session_id,
        ]);

        return back()->with('success', 'Course assigned successfully');
    }

    public function reassign(CourseAssignment $assignment, Request $request)
    {
        $this->assertInHodDepartment($assignment);
        $request->validate([
            'lecturer_id' => 'required|exists:users,id',
        ]);

        $assignment->update(['lecturer_id' => $request->lecturer_id]);
        return back()->with('success', 'Course reassigned successfully');
    }

    public function removeAssignment(CourseAssignment $assignment)
    {
        $this->assertInHodDepartment($assignment);
        $assignment->delete();
        return back()->with('success', 'Assignment removed successfully');
    }

    private function assertInHodDepartment(CourseAssignment $assignment): void
    {
        $user = auth()->user();
        if (!$user || !$user->department_id) {
            abort(403, 'You are not assigned to a department.');
        }
        $course = Course::find($assignment->course_id);
        if (!$course || (int) $course->department_id !== (int) $user->department_id) {
            abort(403, 'You are not allowed to act on this assignment.');
        }
    }
}