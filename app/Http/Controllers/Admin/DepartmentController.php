<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\School;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('school')->get();
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $schools = School::all();
        return view('admin.departments.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:departments',
            'school_id' => 'required|exists:schools,id',
            'description' => 'nullable|string',
        ]);

        Department::create($validated);
        return redirect()->route('admin.departments.index')->with('success', 'Department created');
    }

    public function edit(Department $department)
    {
        $schools = School::all();
        return view('admin.departments.edit', compact('department', 'schools'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:departments,code,' . $department->id,
            'school_id' => 'required|exists:schools,id',
            'description' => 'nullable|string',
        ]);

        $department->update($validated);
        return redirect()->route('admin.departments.index')->with('success', 'Department updated');
    }

    public function destroy(Department $department)
    {
        $department->delete();
        return back()->with('success', 'Department deleted');
    }
}