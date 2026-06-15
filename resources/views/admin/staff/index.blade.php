@extends('layouts.app')

@section('title', 'Manage Staff')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Manage Staff</h4>
    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Staff
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>{{ $member->email }}</td>
                        <td>{{ $member->role->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $member->is_active ? 'success' : 'danger' }}">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.staff.edit', $member) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this staff member">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Reset password" data-bs-toggle="modal" data-bs-target="#resetPasswordModal{{ $member->id }}">
                                <i class="fas fa-key"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.users.activate', $member) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-{{ $member->is_active ? 'danger' : 'success' }}" data-bs-toggle="tooltip" title="{{ $member->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="fas fa-{{ $member->is_active ? 'ban' : 'check' }}"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Reset Password Modal -->
                    <div class="modal fade" id="resetPasswordModal{{ $member->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reset Password for {{ $member->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.staff.reset_password', $member) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                                            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required minlength="8">
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
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No staff members found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection