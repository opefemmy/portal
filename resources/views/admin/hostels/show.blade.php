@extends('layouts.app')

@section('title', $hostel->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-bed me-2"></i>{{ $hostel->name }}</h4>
    <div>
        <a href="{{ route('admin.hostels.createRoom', $hostel) }}" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Add Room
        </a>
        <a href="{{ route('admin.hostels.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Type</small><h5>{{ ucfirst($hostel->type ?? '—') }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Gender</small><h5>{{ ucfirst($hostel->gender ?? '—') }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Capacity</small><h5>{{ $hostel->capacity }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small class="text-muted">Available Rooms</small><h5 class="text-success">{{ $hostel->available_rooms }}</h5></div></div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Rooms</h5></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead class="table-light">
                    <tr><th>Room #</th><th>Floor</th><th>Type</th><th>Capacity</th><th>Available Beds</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($rooms as $r)
                        <tr>
                            <td><strong>{{ $r->room_number }}</strong></td>
                            <td>{{ $r->floor ?? '—' }}</td>
                            <td>{{ $r->type ?? '—' }}</td>
                            <td>{{ $r->capacity }}</td>
                            <td><span class="badge bg-{{ $r->available_beds > 0 ? 'success' : 'danger' }}">{{ $r->available_beds }}</span></td>
                            <td><span class="badge bg-{{ $r->is_active ? 'success' : 'secondary' }}">{{ $r->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No rooms yet — click "Add Room" to create the first one.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $rooms->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
