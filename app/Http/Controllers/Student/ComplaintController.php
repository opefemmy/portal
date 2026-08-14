<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Student;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('student.complaints.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $complaints = Complaint::where('student_id', $student->id)->latest()->get();
        return view('student.complaints', compact('complaints'));
    }

    public function store(Request $request)
    {
        $this->requirePermission('student.complaints.manage');
        $student = Student::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'category' => 'required|in:complaint,suggestion,inquiry',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        $validated['student_id'] = $student->id;
        Complaint::create($validated);

        return back()->with('success', 'Your complaint has been submitted successfully');
    }
}