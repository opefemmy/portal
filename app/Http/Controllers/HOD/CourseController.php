<?php

namespace App\Http\Controllers\HOD;

use App\Http\Controllers\Controller;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return view('hod.courses');
    }

    public function assign()
    {
        $courses = Course::where('department_id', auth()->user()->department_id)->get();
        $lecturers = User::where('role_id', 7)->get(); // Lecturer role
        return view('hod.courses-assign', compact('courses', 'lecturers'));
    }

    public function storeAssignment(Request $request)
    {
        CourseAssignment::create($request->all());
        return back()->with('success', 'Course assigned');
    }

    public function reassign(CourseAssignment $assignment, Request $request)
    {
        $assignment->update(['lecturer_id' => $request->lecturer_id]);
        return back()->with('success', 'Course reassigned');
    }

    public function removeAssignment(CourseAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Assignment removed');
    }
}