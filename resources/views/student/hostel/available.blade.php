@extends('layouts.app')

@section('title', 'Available Hostels')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Available Hostels</h4>
    <a href="{{ route('student.hostel.my') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to My Hostel
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select name="gender" class="form-select">
                    <option value="">All Genders</option>
                    <option value="Male" {{ request('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ request('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    @forelse($hostels as $hostel)
        @php
            // Per-room live availability — count beds with status='available'.
            // Single source of truth, immune to a stale available_beds
            // column. Then bucket rooms into:
            //   - roomsWithBeds: rooms a student can apply for (grouped by floor)
            //   - totalRooms: every active room (used for the floor breakdown
            //     in the modal so a registrar-facing "No rooms configured"
            //     state and a "Full" state are distinguishable)
            $roomsWithBeds = $hostel->rooms->filter(fn ($r) => $r->live_available_beds > 0);
            $hasAnyRooms   = $hostel->rooms->isNotEmpty();
        @endphp
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5>{{ $hostel->name }}</h5>
                    <p class="text-muted">{{ $hostel->code }}</p>
                    <p><strong>Type:</strong> {{ $hostel->type }}</p>
                    <p><strong>Gender:</strong> {{ $hostel->gender }}</p>
                    <p><strong>Capacity:</strong> {{ $hostel->capacity }}</p>
                    <p>
                        <strong>Available Rooms:</strong>
                        {{ $roomsWithBeds->count() }}
                        <small class="text-muted">/ {{ $hostel->rooms->count() }}</small>
                    </p>
                    @if($hostel->location)
                    <p><strong>Location:</strong> {{ $hostel->location }}</p>
                    @endif

                    {{-- Three-way availability badge. Previously this was a
                         binary `> 0 ? Apply : Full` that rendered "Full" on
                         brand-new hostels with zero rooms (the eager-load
                         returned an empty collection, available_rooms = 0,
                         the Apply button hid, the Full badge showed). --}}
                    @if(! $hasAnyRooms)
                        <span class="badge bg-secondary">
                            <i class="fas fa-info-circle me-1"></i>No Rooms Configured
                        </span>
                    @elseif($roomsWithBeds->isEmpty())
                        <span class="badge bg-danger">
                            <i class="fas fa-ban me-1"></i>Full
                        </span>
                    @else
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal{{ $hostel->id }}">
                            <i class="fas fa-check me-1"></i> Apply
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Apply modal: rooms grouped by floor. The old layout was a flat
             list keyed by room id; replacing it with per-floor headers so
             a student applying for "Room 5, Floor 2" can see the other
             Floor-2 rooms in context, with floor names humanised
             (Ground / First / Second / Third / …). --}}
        @if($roomsWithBeds->isNotEmpty())
        <div class="modal fade" id="applyModal{{ $hostel->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('student.hostel.apply') }}" method="POST">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Apply for {{ $hostel->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="hostel_id" value="{{ $hostel->id }}">

                            @if($roomsWithBeds->groupBy('floor')->isEmpty())
                                <p class="text-muted mb-0">No rooms with free beds on any floor.</p>
                            @else
                                <div class="mb-2">
                                    <label class="form-label">Select Room</label>
                                    <select name="hostel_room_id" class="form-select" required>
                                        <option value="">Select Room</option>
                                        @foreach($roomsWithBeds->groupBy('floor')->sortKeys() as $floor => $roomsOnFloor)
                                            <optgroup label="{{ \App\Models\HostelRoom::floorName((int) $floor) }}">
                                                @foreach($roomsOnFloor->sortBy('room_number') as $room)
                                                    <option value="{{ $room->id }}">
                                                        Room {{ $room->room_number }}
                                                        — {{ $room->live_available_beds }} / {{ $room->capacity }} beds available
                                                        @if($room->type)
                                                            ({{ $room->type }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Floor-by-floor breakdown so the student can
                                     see at a glance which floors have space. --}}
                                <div class="mt-3">
                                    <h6 class="mb-2 text-muted">Rooms by Floor</h6>
                                    <div class="row g-2">
                                        @foreach($roomsWithBeds->groupBy('floor')->sortKeys() as $floor => $roomsOnFloor)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="border rounded p-2 h-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <strong class="small">{{ \App\Models\HostelRoom::floorName((int) $floor) }}</strong>
                                                        <span class="badge bg-success">{{ $roomsOnFloor->count() }} free</span>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($roomsOnFloor->sortBy('room_number') as $room)
                                                            <span class="badge bg-light text-dark border"
                                                                  title="Room {{ $room->room_number }} — {{ $room->live_available_beds }}/{{ $room->capacity }} beds available">
                                                                {{ $room->room_number }}
                                                                <small class="text-muted">({{ $room->live_available_beds }})</small>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @empty
        <div class="col-12">
            <p class="text-center">No hostels available</p>
        </div>
    @endforelse
</div>

<div class="d-flex justify-content-center">
    {{ $hostels->links() }}
</div>
@endsection