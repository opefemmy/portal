@extends('layouts.app')

@section('title', 'Hostels')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Hostels</h4>
    <div>
        <a href="{{ route('admin.hostels.allocations') }}" class="btn btn-info me-2">
            <i class="fas fa-users me-2"></i>Allocations
        </a>
        <a href="{{ route('admin.hostels.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Hostel
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" name="search" class="form-control" placeholder="Search hostels..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Search</button>
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
                <div class="row">
                    <div class="col-6">
                        <p><strong>Type:</strong> {{ $hostel->type }}</p>
                        <p><strong>Gender:</strong> {{ $hostel->gender }}</p>
                    </div>
                    <div class="col-6">
                        <p><strong>Capacity:</strong> {{ $hostel->capacity }}</p>
                        <p><strong>Available:</strong> {{ $hostel->available_rooms }} rooms</p>
                    </div>
                </div>
                @if($hostel->location)
                <p><strong>Location:</strong> {{ $hostel->location }}</p>
                @endif
                <div class="mt-2">
                    <a href="{{ route('admin.hostels.show', $hostel) }}" class="btn btn-sm btn-primary">View Rooms</a>
                    <a href="{{ route('admin.hostels.edit', $hostel) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <p class="text-center">No hostels found</p>
    </div>
    @endforelse
</div>

<div class="d-flex justify-content-center">
    {{ $hostels->links() }}
</div>
@endsection