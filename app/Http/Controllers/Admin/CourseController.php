<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('school', 'department', 'programme')->get();
        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        $data = [
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
        ];
        return view('admin.courses.create', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'title' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:10',
            'semester' => 'required|in:first,second',
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
        ]);

        // Check unique constraint: school + dept + prog + level + code
        $exists = Course::where('code', $validated['code'])
            ->where('school_id', $validated['school_id'])
            ->where('department_id', $validated['department_id'])
            ->where('programme_id', $validated['programme_id'])
            ->where('level', $validated['level'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This course already exists for the selected criteria.');
        }

        Course::create($validated);
        return redirect()->route('admin.courses.index')->with('success', 'Course created');
    }

    public function edit(Course $course)
    {
        $data = [
            'course' => $course,
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
        ];
        return view('admin.courses.edit', $data);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'title' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:10',
            'semester' => 'required|in:first,second',
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
        ]);

        $course->update($validated);
        return redirect()->route('admin.courses.index')->with('success', 'Course updated');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return back()->with('success', 'Course deleted');
    }
}