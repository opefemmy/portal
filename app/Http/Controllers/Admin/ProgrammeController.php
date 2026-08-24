<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Programme;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('admin.programmes.manage');
        $programmes = Programme::all();
        return view('admin.programmes.index', compact('programmes'));
    }

    public function create()
    {
        $this->requirePermission('admin.programmes.manage');
        $departments = \App\Models\Department::orderBy('name')->pluck('name', 'id');
        return view('admin.programmes.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.programmes.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:programmes',
            'type' => 'required|in:ND,HND,Degree,PGD,Masters,PhD',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        Programme::create($validated);
        return redirect()->route('admin.programmes.index')->with('success', 'Programme created');
    }

    public function edit(Programme $programme)
    {
        $this->requirePermission('admin.programmes.manage');
        $departments = \App\Models\Department::orderBy('name')->pluck('name', 'id');
        return view('admin.programmes.edit', compact('programme', 'departments'));
    }

    public function update(Request $request, Programme $programme)
    {
        $this->requirePermission('admin.programmes.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:programmes,code,' . $programme->id,
            'type' => 'required|in:ND,HND,Degree,PGD,Masters,PhD',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        $programme->update($validated);
        return redirect()->route('admin.programmes.index')->with('success', 'Programme updated');
    }

    public function destroy(Programme $programme)
    {
        $this->requirePermission('admin.programmes.manage');
        $programme->delete();
        return back()->with('success', 'Programme deleted');
    }
}