<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\School;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('admin.departments.manage');
        $departments = Department::with('school')->get();
        return view('admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $this->requirePermission('admin.departments.manage');
        $schools = School::all();
        return view('admin.departments.create', compact('schools'));
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.departments.manage');
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
        $this->requirePermission('admin.departments.manage');
        $schools = School::all();
        return view('admin.departments.edit', compact('department', 'schools'));
    }

    public function update(Request $request, Department $department)
    {
        $this->requirePermission('admin.departments.manage');
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
        $this->requirePermission('admin.departments.manage');
        $department->delete();
        return back()->with('success', 'Department deleted');
    }
}