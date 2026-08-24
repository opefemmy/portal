@extends('layouts.app')

@section('title', 'Edit Hostel')

@section('content')
<div class="page-header"><h4><i class="fas fa-edit me-2"></i>Edit {{ $hostel->name }}</h4></div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hostels.update', $hostel) }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $hostel->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        @foreach(['male','female','mixed'] as $t)
                            <option value="{{ $t }}" {{ old('type', $hostel->type) == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        @foreach(['male','female','mixed'] as $g)
                            <option value="{{ $g }}" {{ old('gender', $hostel->gender) == $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Capacity (beds)</label>
                    <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $hostel->capacity) }}" min="0" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="{{ old('location', $hostel->location) }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $hostel->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Changes</button>
                <a href="{{ route('admin.hostels.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
