@extends('layouts.app')

@section('title', 'Manage Staff')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Manage Staff</h4>
    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Staff
    </a>
</div>

{{-- Filter Section --}}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Staff</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.staff.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search by Name/Email</label>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ $search ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Role</label>
                <select name="role_slug" class="form-select">
                    <option value="">All Roles</option>
                    @foreach($staffRoles as $role)
                    <option value="{{ $role->slug }}" {{ $roleSlug == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Staff Summary --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">Total Staff</h6>
                <h3>{{ $staff->total() }}</h3>
            </div>
        </div>
    </div>
    @foreach($staffRoles->take(3) as $role)
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <h6 class="text-muted">{{ $role->name }}</h6>
                <h3>{{ $staff->where('role_id', $role->id)->count() }}</h3>
            </div>
        </div>
    </div>
    @endforeach
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
                            <a href="{{ route('admin.staff.show', $member) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.staff.edit', $member) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Reset password" data-bs-toggle="modal" data-bs-target="#resetPasswordModal{{ $member->id }}">
                                <i class="fas fa-key"></i>
                            </button>
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
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No staff members found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $staff->links() }}
    </div>
</div>
@endsection