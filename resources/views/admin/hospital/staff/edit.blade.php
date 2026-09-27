@extends('layouts.app')

@section('title', 'Edit Hospital Staff')

@section('content')
<div class="page-header">
    <h4>Edit Hospital Staff Member</h4>
    <a href="{{ route('admin.hospital-staff.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Directory
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hospital-staff.update', ['staff' => $staff->id]) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- User Account Section -->
                <div class="col-md-6">
                    <h5 class="mb-3 text-primary border-bottom pb-2">Account Information</h5>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" id="name" value="{{ old('name', $staff->user->name ?? '') }}" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" id="email" value="{{ old('email', $staff->user->email ?? '') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" id="password">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">System Role</label>
                        <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" id="role_id" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $staff->user->role_id ?? '') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <!-- Clinical Profile Section -->
                <div class="col-md-6">
                    <h5 class="mb-3 text-primary border-bottom pb-2">Clinical Profile</h5>
                    <div class="mb-3">
                        <label for="staff_number" class="form-label">Staff ID Number</label>
                        <input type="text" name="staff_number" class="form-control @error('staff_number') is-invalid @enderror" id="staff_number" value="{{ old('staff_number', $staff->staff_number) }}" required>
                        @error('staff_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control @error('first_name') the is-invalid @enderror" id="first_name" value="{{ old('first_name', $staff->first_name) }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" id="last_name" value="{{ old('last_name', $staff->last_name) }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="staff_type" class="form-label">Staff Type</label>
                        <select name="staff_type" class="form-select @error('staff_type') is-invalid @enderror" id="staff_type" required>
                            <option value="">Select Type</option>
                            @foreach($staffTypes as $key => $label)
                                <option value="{{ $key }}" {{ old('staff_type', $staff->staff_type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('staff_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" id="phone" value="{{ old('phone', $staff->phone) }}">
                        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="license_number" class="form-label">License Number</label>
                        <input type="text" name="license_number" class="form-control @error('license_number') is-invalid @enderror" id="license_number" value="{{ old('license_number', $staff->license_number) }}">
                        @error('license_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="license_expiry" class="form-label">License Expiry</label>
                            <input type="date" name="license_expiry" class="form-control @error('license_expiry') is-invalid @enderror" id="license_expiry" value="{{ old('license_expiry', $staff->license_expiry ? $staff->license_expiry->format('Y-m-d') : '') }}">
                            @error('license_expiry') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control @error('specialization') is-invalid @enderror" id="specialization" value="{{ old('specialization', $staff->specialization) }}">
                            @error('specialization') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ $staff->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active Status</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_available" id="is_available" value="1" {{ $staff->is_available ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_available">Currently Available</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
                <a href="{{ route('admin.hospital-staff.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection