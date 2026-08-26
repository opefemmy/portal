@extends('layouts.app')

@section('title', "Room {$room->room_number} — {$hostel->name}")

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>
        <i class="fas fa-door-open me-2"></i>
        Room {{ $room->room_number }}
        <small class="text-muted ms-2">— {{ $hostel->name }}</small>
    </h4>
    <div>
        <a href="{{ route('admin.hostels.show', $hostel) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to {{ $hostel->name }}
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Floor</small><h5>{{ \App\Models\HostelRoom::floorName((int) $room->floor) }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Type</small><h5>{{ $room->type ?? '—' }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Capacity</small><h5>{{ $room->capacity }} {{ Str::plural('bed', $room->capacity) }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Available Beds</small><h5 class="text-{{ $room->available_beds > 0 ? 'success' : 'danger' }}">{{ $room->available_beds }}</h5></div></div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Bed Occupancy</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead class="table-light">
                    <tr>
                        <th>Bed</th>
                        <th>Status</th>
                        <th>Student</th>
                        <th>Matric No.</th>
                        <th>Department</th>
                        <th>Session</th>
                        <th>Check-in Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($beds as $bed)
                        @php
                            // Active allocation is the source of truth for who
                            // occupies the bed. Falls back to the bed's own
                            // student_id (denormalised at allocation time by
                            // storeAllocation) when the allocation row is
                            // missing — guards against legacy data where the
                            // allocation row was deleted but the bed row was
                            // not refreshed.
                            $allocation = $bed->allocations->firstWhere('status', 'active')
                                ?? $bed->allocations->first();
                            $student = $bed->student;
                        @endphp
                        <tr>
                            <td><strong>{{ $bed->bed_number }}</strong></td>
                            <td>
                                <span class="badge bg-{{ $bed->status === 'available' ? 'success' : ($bed->status === 'occupied' ? 'secondary' : 'warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $bed->status)) }}
                                </span>
                            </td>
                            <td>
                                @if($student && $student->user)
                                    {{ $student->user->name }}
                                @elseif($student)
                                    {{ $student->matric_number }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $student->matric_number ?? '—' }}</td>
                            <td>{{ $student->department->name ?? '—' }}</td>
                            <td>{{ $allocation->session->name ?? '—' }}</td>
                            <td>
                                @if($allocation && $allocation->check_in_date)
                                    {{ \Carbon\Carbon::parse($allocation->check_in_date)->format('M d, Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No beds configured for this room.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
