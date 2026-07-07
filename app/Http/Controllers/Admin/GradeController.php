<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        $grades = Grade::orderBy('min_score', 'desc')->get();
        return view('admin.grades.index', compact('grades'));
    }

    public function create()
    {
        return view('admin.grades.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:0|max:100|gte:min_score',
            'grade' => 'required|string|max:5',
            'grade_point' => 'required|numeric|min:0|max:5',
            'remark' => 'required|string|max:255',
            'classification' => 'nullable|string',
            'gpa_weight' => 'nullable|integer|min:0|max:5',
        ]);

        Grade::create($validated);
        return redirect()->route('admin.grades.index')->with('success', 'Grade created');
    }

    public function edit(Grade $grade)
    {
        return view('admin.grades.edit', compact('grade'));
    }

    public function update(Request $request, Grade $grade)
    {
        $validated = $request->validate([
            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:0|max:100|gte:min_score',
            'grade' => 'required|string|max:5',
            'grade_point' => 'required|numeric|min:0|max:5',
            'remark' => 'required|string|max:255',
            'classification' => 'nullable|string',
            'gpa_weight' => 'nullable|integer|min:0|max:5',
        ]);

        $grade->update($validated);
        return redirect()->route('admin.grades.index')->with('success', 'Grade updated');
    }

    public function destroy(Grade $grade)
    {
        $grade->delete();
        return back()->with('success', 'Grade deleted');
    }
}