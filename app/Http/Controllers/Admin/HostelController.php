<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Hostel;
use App\Models\HostelRoom;
use App\Models\HostelAllocation;
use App\Models\Student;
use App\Models\Session;
use Illuminate\Http\Request;

class HostelController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('admin.hostels.manage');
        $query = Hostel::query();
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%");
        }
        $hostels = $query->latest()->paginate(20);
        return view('admin.hostels.index', compact('hostels'));
    }

    public function create()
    {
        $this->requirePermission('admin.hostels.manage');
        return view('admin.hostels.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission('admin.hostels.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:hostels',
            'type' => 'required|in:Male,Female,Mixed',
            'gender' => 'required|in:Male,Female,Both',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        // `available_rooms` is now derived from `hostel_rooms.available_beds > 0`
        // via Hostel::getAvailableRoomsAttribute(). Don't write a stale value
        // here — a brand-new hostel with zero rooms must NOT show as "Full".
        $validated['available_rooms'] = 0;
        $validated['is_active'] = $request->is_active ?? true;

        $hostel = Hostel::create($validated);
        $hostel->recomputeAndSave();
        return redirect()->route('admin.hostels.index')->with('success', 'Hostel created successfully');
    }

    public function show(Hostel $hostel)
    {
        $this->requirePermission('admin.hostels.manage');
        $rooms = $hostel->rooms()->latest()->paginate(20);
        return view('admin.hostels.show', compact('hostel', 'rooms'));
    }

    /**
     * Per-room occupant view. Lists every bed in the room with the
     * student currently occupying it (or "Available" if the bed is free).
     *
     * Eager-loads the bed → allocation → student → user chain in one
     * round-trip so the table doesn't N+1 the database. The "active"
     * status on `hostel_allocations` is the source of truth here —
     * checked-out / cancelled rows are filtered out so a previous
     * occupant doesn't appear after they've moved out.
     */
    public function showRoom(Hostel $hostel, HostelRoom $room)
    {
        $this->requirePermission('admin.hostels.manage');

        // Scope the room to the hostel — passing a room from a different
        // hostel would 404 silently otherwise.
        abort_unless($room->hostel_id === $hostel->id, 404);

        $beds = $room->beds()
            ->with([
                'student.user',
                'allocations' => function ($q) {
                    $q->where('status', 'active')->with('session');
                },
            ])
            ->orderBy('bed_number')
            ->get();

        return view('admin.hostels.room-show', compact('hostel', 'room', 'beds'));
    }

    public function edit(Hostel $hostel)
    {
        $this->requirePermission('admin.hostels.manage');
        return view('admin.hostels.edit', compact('hostel'));
    }

    public function update(Request $request, Hostel $hostel)
    {
        $this->requirePermission('admin.hostels.manage');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:hostels,code,' . $hostel->id,
            'type' => 'required|in:Male,Female,Mixed',
            'gender' => 'required|in:Male,Female,Both',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->is_active ?? true;
        $hostel->update($validated);
        return redirect()->route('admin.hostels.index')->with('success', 'Hostel updated successfully');
    }

    public function destroy(Hostel $hostel)
    {
        $this->requirePermission('admin.hostels.manage');
        $hostel->delete();
        return back()->with('success', 'Hostel deleted successfully');
    }

    // Room Management
    public function createRoom(Hostel $hostel)
    {
        $this->requirePermission('admin.hostels.manage');
        return view('admin.hostels.room-create', compact('hostel'));
    }

    public function storeRoom(Request $request, Hostel $hostel)
    {
        $this->requirePermission('admin.hostels.manage');
        $validated = $request->validate([
            'room_number' => 'required|string|max:20',
            'floor' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'type' => 'nullable|string',
        ]);

        $validated['hostel_id'] = $hostel->id;
        $validated['available_beds'] = $validated['capacity'];
        $validated['is_active'] = true;

        $room = HostelRoom::create($validated);

        // Create beds
        for ($i = 1; $i <= $validated['capacity']; $i++) {
            $room->beds()->create([
                'bed_number' => 'Bed ' . $i,
                'status' => 'available'
            ]);
        }

        // Recompute the hostel's available_rooms from the rooms
        // table — the previous `$hostel->increment('available_rooms')`
        // could undercount after the hostel's available_rooms was
        // hard-set to 0 by `store()` and never refreshed.
        $hostel->recomputeAndSave();

        return redirect()->route('admin.hostels.show', $hostel)->with('success', 'Room created with ' . $validated['capacity'] . ' beds');
    }

    // Allocation Management
    public function allocations(Request $request)
    {
        $this->requirePermission('admin.hostels.manage');
        $query = HostelAllocation::with(['hostel', 'room', 'student.user', 'session']);
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $allocations = $query->latest()->paginate(20);
        return view('admin.hostels.allocations', compact('allocations'));
    }

    public function createAllocation()
    {
        $this->requirePermission('admin.hostels.manage');
        $hostels = Hostel::where('is_active', true)->get();
        $students = Student::where('status', 'active')->with('user')->get();
        $sessions = Session::all();
        return view('admin.hostels.allocation-create', compact('hostels', 'students', 'sessions'));
    }

    public function storeAllocation(Request $request)
    {
        $this->requirePermission('admin.hostels.manage');
        $validated = $request->validate([
            'hostel_id' => 'required|exists:hostels,id',
            'hostel_room_id' => 'required|exists:hostel_rooms,id',
            'student_id' => 'required|exists:students,id',
            'session_id' => 'required|exists:sessions,id',
            'check_in_date' => 'required|date',
        ]);

        // Check if bed is available
        $bed = \App\Models\HostelBed::where('hostel_room_id', $validated['hostel_room_id'])
            ->where('status', 'available')
            ->first();

        if (!$bed) {
            return back()->with('error', 'No available beds in this room');
        }

        // Check if student already allocated
        $exists = HostelAllocation::where('student_id', $validated['student_id'])
            ->where('session_id', $validated['session_id'])
            ->where('status', 'active')
            ->first();

        if ($exists) {
            return back()->with('error', 'Student already has an active hostel allocation for this session');
        }

        $allocation = HostelAllocation::create([
            'hostel_id' => $validated['hostel_id'],
            'hostel_room_id' => $validated['hostel_room_id'],
            'student_id' => $validated['student_id'],
            'bed_id' => $bed->id,
            'session_id' => $validated['session_id'],
            'check_in_date' => $validated['check_in_date'],
            'status' => 'active'
        ]);

        $bed->update(['status' => 'occupied', 'student_id' => $validated['student_id']]);

        // Update available beds
        $room = HostelRoom::find($validated['hostel_room_id']);
        $room->decrement('available_beds');

        // Refresh the hostel's `available_rooms` so the student
        // dashboard reflects the new reality. Without this, the
        // counter only changes when a room is added, never when
        // a bed is filled.
        $hostel = Hostel::find($validated['hostel_id']);
        if ($hostel) {
            $hostel->recomputeAndSave();
        }

        return redirect()->route('admin.hostels.allocations')->with('success', 'Student allocated to hostel successfully');
    }

    public function getRooms($hostelId)
    {
        $this->requirePermission('admin.hostels.manage');
        $rooms = HostelRoom::where('hostel_id', $hostelId)
            ->where('is_active', true)
            ->where('available_beds', '>', 0)
            ->get();
        return response()->json($rooms);
    }

    public function getAvailableBeds($roomId)
    {
        $this->requirePermission('admin.hostels.manage');
        $beds = \App\Models\HostelBed::where('hostel_room_id', $roomId)
            ->where('status', 'available')
            ->get();
        return response()->json($beds);
    }

    public function checkOut(HostelAllocation $allocation)
    {
        $this->requirePermission('admin.hostels.manage');
        $allocation->update([
            'check_out_date' => now()->toDateString(),
            'status' => 'checked_out'
        ]);

        $hostel = $allocation->hostel;
        if ($allocation->bed_id) {
            $bed = \App\Models\HostelBed::find($allocation->bed_id);
            if ($bed) {
                $bed->update(['status' => 'available', 'student_id' => null]);
                $bed->room->increment('available_beds');
            }
        }

        // Same reason as storeAllocation — keep the hostel's
        // available_rooms counter in sync with reality.
        if ($hostel) {
            $hostel->recomputeAndSave();
        }

        return back()->with('success', 'Student checked out successfully');
    }
}