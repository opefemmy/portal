<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeClassification;
use App\Models\GradingScale;
use Illuminate\Http\Request;

class GradingController extends Controller
{
    public function index()
    {
        $classifications = GradeClassification::orderBy('sort_order')->get();
        $gradingScales = GradingScale::orderBy('sort_order')->get();
        return view('admin.grades.index', compact('classifications', 'gradingScales'));
    }

    public function updateClassification(Request $request, GradeClassification $classification)
    {
        $validated = $request->validate([
            'min_gpa' => 'required|numeric|min:0|max:5',
            'max_gpa' => 'required|numeric|min:0|max:5',
            'description' => 'nullable|string',
        ]);

        $classification->update($validated);
        return back()->with('success', 'Classification updated successfully');
    }

    public function updateScale(Request $request, GradingScale $scale)
    {
        $validated = $request->validate([
            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:0|max:100',
            'grade_point' => 'required|numeric|min:0|max:5',
            'gpa_weight' => 'required|numeric|min:0|max:5',
            'remark' => 'required|string',
            'classification' => 'nullable|string',
        ]);

        $scale->update($validated);
        return back()->with('success', 'Grading scale updated successfully');
    }

    public function storeClassification(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:grade_classifications,slug',
            'min_gpa' => 'required|numeric|min:0|max:5',
            'max_gpa' => 'required|numeric|min:0|max:5',
            'description' => 'nullable|string',
        ]);

        $validated['sort_order'] = GradeClassification::max('sort_order') + 1;
        GradeClassification::create($validated);
        return back()->with('success', 'Classification created successfully');
    }

    public function storeScale(Request $request)
    {
        $validated = $request->validate([
            'grade' => 'required|string|unique:grading_scales,grade',
            'min_score' => 'required|integer|min:0|max:100',
            'max_score' => 'required|integer|min:0|max:100',
            'grade_point' => 'required|numeric|min:0|max:5',
            'gpa_weight' => 'required|numeric|min:0|max:5',
            'remark' => 'required|string',
            'classification' => 'nullable|string',
        ]);

        $validated['sort_order'] = GradingScale::max('sort_order') + 1;
        GradingScale::create($validated);
        return back()->with('success', 'Grading scale created successfully');
    }

    public function destroyClassification(GradeClassification $classification)
    {
        $classification->delete();
        return back()->with('success', 'Classification deleted successfully');
    }

    public function destroyScale(GradingScale $scale)
    {
        $scale->delete();
        return back()->with('success', 'Grading scale deleted successfully');
    }
}