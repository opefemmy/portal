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
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
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
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5>{{ $hostel->name }}</h5>
                <p class="text-muted">{{ $hostel->code }}</p>
                <p><strong>Type:</strong> {{ $hostel->type }}</p>
                <p><strong>Gender:</strong> {{ $hostel->gender }}</p>
                <p><strong>Capacity:</strong> {{ $hostel->capacity }}</p>
                <p><strong>Available Rooms:</strong> {{ $hostel->available_rooms }}</p>
                @if($hostel->location)
                <p><strong>Location:</strong> {{ $hostel->location }}</p>
                @endif

                @if($hostel->available_rooms > 0)
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#applyModal{{ $hostel->id }}">
                    Apply
                </button>
                @else
                <span class="badge bg-danger">Full</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Apply Modal -->
    @if($hostel->available_rooms > 0)
    <div class="modal fade" id="applyModal{{ $hostel->id }}" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('student.hostel.apply') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Apply for {{ $hostel->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="hostel_id" value="{{ $hostel->id }}">
                        <div class="mb-3">
                            <label class="form-label">Select Room</label>
                            <select name="hostel_room_id" class="form-select" required id="roomSelect{{ $hostel->id }}">
                                <option value="">Select Room</option>
                                @foreach($hostel->rooms as $room)
                                    @if($room->available_beds > 0)
                                    <option value="{{ $room->id }}">
                                        Room {{ $room->room_number }} - Floor {{ $room->floor }} - {{ $room->available_beds }} beds available
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
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