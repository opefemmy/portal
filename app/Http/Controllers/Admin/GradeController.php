<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\GradeClassification;
use App\Models\GradingScale;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('admin.grades.manage');
        $grades = Grade::orderBy('min_score', 'desc')->get();
        $classifications = GradeClassification::orderBy('sort_order')->get();
        $gradingScales = GradingScale::orderBy('sort_order')->get();
        return view('admin.grades.index', compact('grades', 'classifications', 'gradingScales'));
    }

    public function create()
    {
        $this->requirePermission('admin.grades.manage');
        return view('admin.grades.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.grades.manage');
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
        $this->requirePermission('admin.grades.manage');
        return view('admin.grades.edit', compact('grade'));
    }

    public function update(Request $request, Grade $grade)
    {
        $this->requirePermission('admin.grades.manage');
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
        $this->requirePermission('admin.grades.manage');
        $grade->delete();
        return back()->with('success', 'Grade deleted');
    }
}