<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::with('departments')->get();
        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('admin.schools.create');
    }

    public function store(Request $request)
    {
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
        return view('admin.schools.show', compact('school'));
    }

    public function edit(School $school)
    {
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
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
        $school->delete();
        return back()->with('success', 'School deleted');
    }
}