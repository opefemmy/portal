<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class CourseAssignmentController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('admin.course-assignments.manage');
        $assignments = CourseAssignment::with(['course', 'lecturer', 'session'])->latest()->get();
        $courses = Course::with(['department', 'school'])->get();
        $sessions = \App\Models\Session::all();
        return view('admin.course-assignments.index', compact('assignments', 'courses', 'sessions'));
    }

    public function create()
    {
        $this->requirePermission('admin.course-assignments.manage');
        $data = [
            'courses' => Course::with(['department', 'school'])->get(),
            'lecturers' => User::whereHas('role', function($query) {
                $query->where('slug', 'lecturer');
            })->get(),
            'departments' => Department::all(),
        ];
        return view('admin.course-assignments.create', $data);
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.course-assignments.manage');
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'lecturer_id' => 'required|exists:users,id',
            'session_id' => 'required|exists:sessions,id',
            'semester' => 'required|in:First,Second',
        ]);

        CourseAssignment::create($validated);
        return redirect()->route('admin.course-assignments.index')->with('success', 'Course assigned to lecturer');
    }

    public function edit(CourseAssignment $assignment)
    {
        $this->requirePermission('admin.course-assignments.manage');
        $data = [
            'assignment' => $assignment,
            'courses' => Course::all(),
            'lecturers' => User::whereHas('role', function($query) {
                $query->where('slug', 'lecturer');
            })->get(),
        ];
        return view('admin.course-assignments.edit', $data);
    }

    public function update(Request $request, CourseAssignment $assignment)
    {
        $this->requirePermission('admin.course-assignments.manage');
        $validated = $request->validate([
            'lecturer_id' => 'required|exists:users,id',
            'semester' => 'required|in:First,Second',
        ]);

        $assignment->update($validated);
        return redirect()->route('admin.course-assignments.index')->with('success', 'Assignment updated');
    }

    public function destroy(CourseAssignment $assignment)
    {
        $this->requirePermission('admin.course-assignments.manage');
        $assignment->delete();
        return back()->with('success', 'Assignment removed');
    }
}