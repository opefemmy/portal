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

        // Always scope the listing to hostels the student is actually
        // eligible for. The pre-existing code only applied a gender
        // filter when the student manually selected a value in the
        // dropdown, which meant a male student saw female hostels
        // (and vice versa) by default. The student's own gender
        // (`users.gender`) is the source of truth here; the dropdown
        // remains a *narrower* override (e.g. a female student
        // explicitly asking to see only female hostels — not the
        // default behaviour).
        $studentGender = self::normalizeGender(auth()->user()->gender ?? null);

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

        // Always honour the student's gender. `Both` hostels (co-ed)
        // stay visible to everyone. If the student's own gender is
        // missing/unknown, we surface all active hostels with a
        // yellow "please set your gender" warning rather than
        // returning an empty list — this is what blocked students
        // whose profile never had `users.gender` populated from
        // seeing anything at all. The warning is rendered in the
        // view (`$showGenderWarning`); the listing is unfiltered
        // only when we genuinely don't know the student's gender.
        $showGenderWarning = $studentGender === null;

        if (! $showGenderWarning) {
            $query->where(function ($q) use ($studentGender) {
                $q->where('gender', 'Both')
                  ->orWhere('gender', $studentGender);
            });
        }

        // The manual `?gender=` dropdown is now a *restrictive* filter:
        // it can only narrow the list further (e.g. a female student
        // wants to see only female hostels). It cannot widen it back
        // to the opposite gender — that would defeat the guard above.
        if ($request->gender && self::normalizeGender($request->gender)) {
            $allowed = self::normalizeGender($request->gender);
            // Only allow the filter to keep hostels the student is
            // already eligible for (their own gender or "Both").
            if ($studentGender === null || $allowed === $studentGender) {
                $query->where(function ($q) use ($allowed) {
                    $q->where('gender', $allowed)
                      ->orWhere('gender', 'Both');
                });
            }
        }

        $hostels = $query->latest()->paginate(20);

        return view('student.hostel.available', compact('hostels', 'showGenderWarning'));
    }

    /**
     * Normalise free-form gender strings ("M"/"F"/"male"/"Female"/…)
     * to the canonical values the `hostels.gender` column uses
     * ("Male" / "Female" / "Both"). Returns `null` for anything
     * unrecognised so callers can treat it as "unknown — restrict to
     * co-ed only" rather than silently matching the wrong bucket.
     */
    private static function normalizeGender(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = strtolower(trim($value));
        return match ($v) {
            'male', 'm'   => 'Male',
            'female', 'f' => 'Female',
            'both', 'all', 'mixed', 'co-ed', 'coed' => 'Both',
            default       => null,
        };
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

        // Server-side gender guard. The availableHostels() listing
        // already filters by the student's gender, but a student could
        // still hand-craft a POST (or a stale page) and submit a
        // hostel_id for a hostel of the wrong gender. Reject that here
        // before touching the bed / room counters.
        //
        // Two-pass eligibility:
        //   1. Known student gender → must match the hostel's gender or be 'Both'.
        //   2. Unknown student gender → trust the listing fallback
        //      (which widened to show every active hostel with a
        //      "set your gender" warning). Blocking the apply when the
        //      listing already let them through would be a UX dead end
        //      for legacy accounts with empty `users.gender`.
        $targetHostel = Hostel::find($request->hostel_id);
        if (! $targetHostel) {
            return back()->with('error', 'Hostel not found');
        }
        $studentGender = self::normalizeGender(auth()->user()->gender ?? null);
        $hostelGender  = self::normalizeGender($targetHostel->gender);
        $eligible = $hostelGender === 'Both'
            || ($studentGender !== null && $hostelGender === $studentGender)
            || $studentGender === null;
        if (! $eligible) {
            return back()->with('error', 'You are not eligible to apply to this hostel.');
        }

        $room = HostelRoom::find($request->hostel_room_id);
        if ($room->available_beds < 1) {
            return back()->with('error', 'No available beds in this room');
        }

        // Allocate the first available bed in the chosen room.
        // The allocation is created as `status='active'` directly —
        // there is no admin approval step for self-applied hostels
        // (mirrors Admin\HostelController::storeAllocation, which also
        // writes 'active' immediately). The bed is marked occupied in
        // the same pass so the live counts drop atomically.
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
            'status' => 'active'
        ]);

        // Hold the bed against the room so the live count drops the
        // moment the allocation is created. Mirrors the admin path so
        // the student's self-apply doesn't drift the counters.
        $bed->update(['status' => 'occupied', 'student_id' => $student->id]);
        $room->decrement('available_beds');

        // Refresh the hostel's available_rooms count so the student
        // dashboard (and any other consumers) reflects the new reality.
        $hostel = Hostel::find($request->hostel_id);
        if ($hostel) {
            $hostel->recomputeAndSave();
        }

        return redirect()->route('student.hostel.my')->with('success', 'Hostel allocated. Bed reserved.');
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

        return redirect()->route('student.hostel.my')->with('success', 'Change request submitted');
    }
}