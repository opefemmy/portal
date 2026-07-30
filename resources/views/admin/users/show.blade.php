@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>User Details</h4>
    <div>
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-info">
            <i class="fas fa-edit me-2"></i>Edit User
        </a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Users
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                    class="rounded-circle mb-3" width="120" height="120">
                <h5>{{ $user->name }}</h5>
                <p class="text-muted">{{ $user->role->name ?? 'No Role' }}</p>
                <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">User Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="30%">Email:</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th>Role:</th>
                        <td>{{ $user->role->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Staff ID:</th>
                        <td>{{ $user->staff_id ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>School:</th>
                        <td>{{ $user->school->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Department:</th>
                        <td>{{ $user->department->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Programme:</th>
                        <td>{{ $user->programme->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Phone:</th>
                        <td>{{ $user->phone ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Gender:</th>
                        <td>{{ $user->gender ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Date of Birth:</th>
                        <td>{{ $user->date_of_birth ? $user->date_of_birth->format('d/m/Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Address:</th>
                        <td>{{ $user->address ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td>{{ $user->created_at->format('d/m/Y h:i A') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
