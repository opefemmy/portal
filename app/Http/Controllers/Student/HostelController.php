<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelAllocation;
use App\Models\Student;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    use EnforcesPermission;

    public function myHostel()
    {
        $this->requirePermission('student.hostel.manage');
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
        $this->requirePermission('student.hostel.manage');

        // Eager-load ALL active rooms (not just those with available beds)
        // so we can:
        //   1. Group rooms by floor in the view
        //   2. Distinguish "no rooms configured" from "Full" — a brand-
        //      new hostel with zero rooms previously rendered as "Full"
        //      because the eager-load filtered to `available_beds > 0`
        //      and the count came back as 0. With all rooms loaded, the
        //      view can branch on `$rooms->isEmpty()` separately.
        // We also load `beds` so each room can use the live
        // `live_available_beds` accessor to render its own "X beds
        // available" copy — single source of truth, immune to the
        // denormalised `available_beds` column drifting out of sync.
        $query = Hostel::where('is_active', true)
            ->with(['rooms' => function ($q) {
                $q->where('is_active', true)->orderBy('floor')->orderBy('room_number');
            }, 'rooms.beds']);

        if ($request->gender) {
            $query->where(function ($q) use ($request) {
                $q->where('gender', $request->gender)
                  ->orWhere('gender', 'Both');
            });
        }

        $hostels = $query->latest()->paginate(20);

        return view('student.hostel.available', compact('hostels'));
    }

    public function apply(Request $request)
    {
        $this->requirePermission('student.hostel.manage');
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
        if (! $bed) {
            return back()->with('error', 'No available beds in this room');
        }

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

        // Hold the bed against the room so the live count drops even
        // before approval. Mirrors Admin\HostelController::storeAllocation
        // so the student's self-apply doesn't drift the counters. The
        // admin can still approve the allocation and the bed is already
        // marked occupied; check-out frees it again.
        $bed->update(['status' => 'occupied', 'student_id' => $student->id]);
        $room->decrement('available_beds');

        // Refresh the hostel's available_rooms count so the student
        // dashboard (and any other consumers) reflects the new reality.
        $hostel = Hostel::find($request->hostel_id);
        if ($hostel) {
            $hostel->recomputeAndSave();
        }

        return redirect()->route('hostel.my')->with('success', 'Hostel application submitted successfully. Pending approval.');
    }

    public function requestChange(Request $request)
    {
        $this->requirePermission('student.hostel.manage');
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

        return redirect()->route('hostel.my')->with('success', 'Change request submitted');
    }
}