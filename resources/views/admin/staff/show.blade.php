@extends('layouts.app')

@section('title', 'Staff Details')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Staff Details</h4>
    <div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Staff
        </a>
        <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                @if($staff->passport)
                    <img src="{{ asset('uploads/passports/' . $staff->passport) }}" alt="Photo" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                @else
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px;">
                        <i class="fas fa-user fa-5x text-muted"></i>
                    </div>
                @endif
                <h4>{{ $staff->name }}</h4>
                <p class="text-muted">{{ $staff->role->name ?? 'No Role' }}</p>
                <span class="badge bg-{{ $staff->is_active ? 'success' : 'danger' }}">
                    {{ $staff->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Staff Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Full Name:</strong></td>
                        <td>{{ $staff->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td>{{ $staff->email }}</td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td>{{ $staff->role->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <span class="badge bg-{{ $staff->is_active ? 'success' : 'danger' }}">
                                {{ $staff->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td>{{ $staff->created_at->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                    <i class="fas fa-key me-2"></i>Reset Password
                </button>
                <form method="POST" action="{{ route('admin.staff.destroy', $staff) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this staff member?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password for {{ $staff->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.staff.reset_password', $staff) }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required minlength="6">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection