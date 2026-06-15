<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\Student;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index()
    {
        $student = Student::where('user_id', auth()->id())->firstOrFail();
        $complaints = Complaint::where('student_id', $student->id)->latest()->get();
        return view('student.complaints', compact('complaints'));
    }

    public function store(Request $request)
    {
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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with(['student.user', 'assignedTo']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $complaints = $query->latest()->paginate(20);
        $staff = User::whereHas('role', function($q) {
            $q->whereIn('slug', ['admin', 'registrar', 'hod']);
        })->get();

        return view('admin.complaints.index', compact('complaints', 'staff'));
    }

    public function respond(Complaint $complaint, Request $request)
    {
        $request->validate(['response' => 'required|string']);

        $complaint->update([
            'response' => $request->response,
            'status' => 'resolved',
            'assigned_to' => auth()->id(),
        ]);

        return back()->with('success', 'Response sent successfully');
    }
}