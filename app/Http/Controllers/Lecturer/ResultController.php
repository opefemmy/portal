<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function enter(\App\Models\Course $course)
    {
        return view('lecturer.results-enter', compact('course'));
    }

    public function store(Request $request, \App\Models\Course $course)
    {
        // Implementation for result entry
        return back()->with('success', 'Results saved');
    }

    public function bulkUpload(Request $request, \App\Models\Course $course)
    {
        // Implementation for Excel bulk upload
        return back()->with('success', 'Results uploaded');
    }
}