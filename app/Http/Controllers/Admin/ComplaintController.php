<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status;

        $query = Complaint::with('student.user');

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $complaints = $query->latest()->paginate(20);

        return view('admin.complaints.index', compact('complaints', 'status'));
    }

    public function show(Complaint $complaint)
    {
        $complaint->load('student.user');
        return view('admin.complaints.show', compact('complaint'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,rejected',
            'admin_response' => 'nullable|string',
        ]);

        $complaint->update($validated);

        return back()->with('success', 'Complaint updated successfully');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();
        return back()->with('success', 'Complaint deleted successfully');
    }
}