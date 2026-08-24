@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Users</h4>
    <div>
        <a href="{{ route('admin.users.upload') }}" class="btn btn-success">
            <i class="fas fa-upload me-2"></i>Upload Users
        </a>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add User
        </a>
    </div>
</div>

{{-- Filter row — `active=1` shows only is_active rows (default: all,
     so deactivated users are still visible to admins). The previous
     version hid them entirely. --}}
<form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <label class="form-label small mb-1">Role</label>
        <select name="role" class="form-select form-select-sm">
            <option value="">All roles</option>
            @foreach($roles as $r)
                <option value="{{ $r->slug }}" {{ request('role') === $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select name="active" class="form-select form-select-sm">
            <option value="0" {{ empty($onlyActive) ? 'selected' : '' }}>All (active + deactivated)</option>
            <option value="1" {{ !empty($onlyActive) ? 'selected' : '' }}>Active only</option>
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-filter me-1"></i>Filter
        </button>
    </div>
    @if(request('role') || !empty($onlyActive))
        <div class="col-md-2 d-grid">
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Clear
            </a>
        </div>
    @endif
</form>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Current Role(s)</th>
                        <th>Add Roles</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        @php
                            // The primary role is whatever `users.role_id`
                            // says — that's the role middleware/redirect
                            // uses. The pivot holds additional roles.
                            // We dedupe so the primary doesn't appear
                            // twice in the pills.
                            $primaryRole = $user->role;
                            $additionalRoles = $user->roles->reject(
                                fn($r) => $primaryRole && $r->id === $primaryRole->id
                            );
                        @endphp
                        <tr class="{{ $user->is_active ? '' : 'table-secondary' }}">
                            <td>
                                {{ $user->email }}
                                @if(! $user->is_active)
                                    <span class="badge bg-secondary ms-1" title="This account is deactivated and cannot log in.">Deactivated</span>
                                @endif
                            </td>
                            <td>
                                {{ $user->name }}
                                @if($user->staff_id)
                                    <small class="text-muted d-block">{{ $user->staff_id }}</small>
                                @endif
                            </td>
                            <td>
                                @if($primaryRole)
                                    <span class="badge bg-primary me-1" title="Primary role — drives login redirect">
                                        <i class="fas fa-star me-1"></i>{{ $primaryRole->name }}
                                    </span>
                                @endif
                                @foreach($additionalRoles as $r)
                                    <span class="badge bg-light text-dark border me-1">{{ $r->name }}</span>
                                @endforeach
                                @if(!$primaryRole && $additionalRoles->isEmpty())
                                    <span class="badge bg-warning text-dark" title="This user has no role assigned — likely a bad seed or deleted role. Edit to assign a role.">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Missing role
                                    </span>
                                @endif
                            </td>
                            <td>
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#roleModal{{ $user->id }}"
                                        title="Add or remove roles">
                                    <i class="fas fa-user-shield me-1"></i>Add Roles
                                </button>
                            </td>
                            <td>
                                <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View user details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this user">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($user->is_active)
                                    <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Deactivate this user (they cannot log in but the row is preserved)">
                                            <i class="fas fa-toggle-off"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Reactivate this user">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.users.reset_password', $user) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Reset password to default" onclick="return confirm('Reset password to default (password)?')">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    {{-- The User model has no SoftDeletes trait, so
                                         this is a HARD delete. The email /
                                         staff_id / matric_number unique indexes
                                         are immediately freed for re-use. Strengthen
                                         the confirm prompt so admins know. --}}
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip"
                                            title="PERMANENTLY delete this user"
                                            onclick="return confirm('⚠ PERMANENT DELETE ⚠\n\nThis will HARD-delete {{ addslashes($user->name) }} ({{ $user->email }}).\n\nThe email, staff ID and matric number will be FREE for re-use — you can immediately add a new user with the same credentials.\n\nContinue?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $users->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- One modal per user on the current page. The partial handles its
     own per-row JS via @once @push. --}}
@foreach($users as $user)
    @include('admin.users._role-modal', ['user' => $user, 'roles' => $roles])
@endforeach
@endsection