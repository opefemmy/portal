<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('admin.schools.manage');
        $schools = School::with('departments')->get();
        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        $this->requirePermission('admin.schools.manage');
        return view('admin.schools.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.schools.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:schools',
            'description' => 'nullable|string',
        ]);

        School::create($validated);
        return redirect()->route('admin.schools.index')->with('success', 'School created');
    }

    public function show(School $school)
    {
        $this->requirePermission('admin.schools.manage');
        return view('admin.schools.show', compact('school'));
    }

    public function edit(School $school)
    {
        $this->requirePermission('admin.schools.manage');
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $this->requirePermission('admin.schools.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:schools,code,' . $school->id,
            'description' => 'nullable|string',
        ]);

        $school->update($validated);
        return redirect()->route('admin.schools.index')->with('success', 'School updated');
    }

    public function destroy(School $school)
    {
        $this->requirePermission('admin.schools.manage');
        // Block the delete if any related row still references this school.
        // We surface the first blocker in the error message so the admin
        // knows what to clean up first. Cascading would silently destroy
        // student records, which is never the right default.
        foreach ([
            'departments'   => fn () => $school->departments()->count(),
            'students'      => fn () => $school->students()->count(),
            'applicants'    => fn () => \App\Models\Applicant::where('school_id', $school->id)->count(),
            'applications'  => fn () => \App\Models\Application::where('school_id', $school->id)->count(),
            'users'         => fn () => \App\Models\User::where('school_id', $school->id)->count(),
            'courses'       => fn () => \App\Models\Course::where('school_id', $school->id)->count(),
            'fees'          => fn () => \App\Models\Fee::where('school_id', $school->id)->count(),
        ] as $label => $countFn) {
            $count = $countFn();
            if ($count > 0) {
                return back()->with(
                    'error',
                    "Cannot delete {$school->name}: {$count} related {$label} record(s) still exist. Remove or reassign them first."
                );
            }
        }

        $school->delete();
        return back()->with('success', 'School deleted');
    }
}