@extends('layouts.app')

@section('title', 'Add Room')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>Add Room to {{ $hostel->name }}</h4></div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hostels.storeRoom', $hostel) }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" class="form-control" value="{{ old('room_number') }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Floor</label>
                    <input type="number" name="floor" class="form-control" value="{{ old('floor', 1) }}" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacity (beds)</label>
                    <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 4) }}" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <input type="text" name="type" class="form-control" value="{{ old('type') }}" placeholder="e.g. Standard, Deluxe">
                </div>
            </div>
            <div class="alert alert-info mt-3 mb-0"><i class="fas fa-info-circle me-2"></i>Adding this room creates the beds automatically and refreshes the hostel's available-rooms count.</div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Room</button>
                <a href="{{ route('admin.hostels.show', $hostel) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
