<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelAllocation;
use App\Models\Student;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    public function myHostel()
    {
        $student = auth()->user()->student;
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found');
        }

        $allocation = HostelAllocation::where('student_id', $student->id)
            ->where('status', 'active')
            ->with(['hostel', 'room', 'session'])
            ->first();

        return view('student.hostel.my-hostel', compact('allocation', 'student'));
    }

    public function availableHostels(Request $request)
    {
        $query = Hostel::where('is_active', true);

        if ($request->gender) {
            $query->where('gender', $request->gender)
                  ->orWhere('gender', 'Both');
        }

        $hostels = $query->latest()->paginate(20);
        return view('student.hostel.available', compact('hostels'));
    }

    public function apply(Request $request)
    {
        $student = auth()->user()->student;
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student profile not found');
        }

        // Check if already has active allocation
        $existing = HostelAllocation::where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return back()->with('error', 'You already have an active hostel allocation');
        }

        $request->validate([
            'hostel_id' => 'required|exists:hostels,id',
            'hostel_room_id' => 'required|exists:hostel_rooms,id',
        ]);

        $room = HostelRoom::find($request->hostel_room_id);
        if ($room->available_beds < 1) {
            return back()->with('error', 'No available beds in this room');
        }

        // Create application (pending approval)
        $bed = $room->beds()->where('status', 'available')->first();

        $session = \App\Models\Session::where('is_current', true)->first();

        HostelAllocation::create([
            'hostel_id' => $request->hostel_id,
            'hostel_room_id' => $request->hostel_room_id,
            'student_id' => $student->id,
            'bed_id' => $bed->id,
            'session_id' => $session?->id,
            'check_in_date' => now()->toDateString(),
            'status' => 'pending'
        ]);

        return redirect()->route('student.hostel.my')->with('success', 'Hostel application submitted successfully. Pending approval.');
    }

    public function requestChange(Request $request)
    {
        $student = auth()->user()->student;

        $request->validate([
            'reason' => 'required|string',
        ]);

        $allocation = HostelAllocation::where('student_id', $student->id)
            ->where('status', 'active')
            ->first();

        if ($allocation) {
            $allocation->update(['status' => 'change_requested']);
        }

        return redirect()->route('student.hostel.my')->with('success', 'Change request submitted');
    }
}