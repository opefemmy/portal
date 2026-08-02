@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-history me-2"></i>Audit Logs</h4>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <!-- Activity Type Legend -->
        <div class="alert alert-light border mb-4">
            <div class="row text-center">
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-success"><i class="fas fa-sign-in-alt me-1"></i>Login</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-danger"><i class="fas fa-sign-out-alt me-1"></i>Logout</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-primary"><i class="fas fa-plus me-1"></i>Create</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-info"><i class="fas fa-edit me-1"></i>Update</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-warning text-dark"><i class="fas fa-trash me-1"></i>Delete</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-purple" style="background-color: #9333ea;"><i class="fas fa-redo me-1"></i>Restore</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-indigo" style="background-color: #6610f2;"><i class="fas fa-download me-1"></i>Export</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-teal" style="background-color: #20c997;"><i class="fas fa-upload me-1"></i>Import</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-orange" style="background-color: #fd7e14;"><i class="fas fa-money-bill me-1"></i>Payment</span>
                </div>
                <div class="col-md col-6 mb-2">
                    <span class="badge bg-pink" style="background-color: #ec4899;"><i class="fas fa-user-plus me-1"></i>Admission</span>
                </div>
            </div>
        </div>

        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    <option value="hospital">Hospital</option>
                    <option value="finance">Finance</option>
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
                    <option value="application">Application</option>
                    <option value="payment">Payment</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="fas fa-filter me-2"></i>Filter
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th><i class="fas fa-clock me-1"></i>Date/Time</th>
                        <th><i class="fas fa-user me-1"></i>User</th>
                        <th><i class="fas fa-cube me-1"></i>Module</th>
                        <th><i class="fas fa-bolt me-1"></i>Action</th>
                        <th><i class="fas fa-align-left me-1"></i>Description</th>
                        <th><i class="fas fa-globe me-1"></i>IP Address</th>
                        <th><i class="fas fa-check-circle me-1"></i>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <small class="text-muted">{{ optional($log->created_at)->format('d M Y') ?? 'N/A' }}</small><br>
                            <span class="fw-semibold">{{ optional($log->created_at)->format('H:i:s') ?? 'N/A' }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    {{ strtoupper(substr($log->user->name ?? 'S', 0, 1)) }}
                                </div>
                                <span>{{ $log->user->name ?? 'System' }}</span>
                            </div>
                        </td>
                        <td>
                            @php
                                $moduleColors = [
                                    'hospital' => 'bg-danger',
                                    'finance' => 'bg-success',
                                    'student' => 'bg-primary',
                                    'staff' => 'bg-info',
                                    'application' => 'bg-pink',
                                    'payment' => 'bg-warning text-dark',
                                ];
                                $moduleColor = $moduleColors[$log->module] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $moduleColor }}">{{ ucfirst($log->module) }}</span>
                        </td>
                        <td>
                            @php
                                $actionColors = [
                                    'login' => 'bg-success',
                                    'logout' => 'bg-danger',
                                    'create' => 'bg-primary',
                                    'update' => 'bg-info',
                                    'delete' => 'bg-warning text-dark',
                                    'restore' => 'bg-purple',
                                    'export' => 'bg-indigo',
                                    'import' => 'bg-teal',
                                    'payment' => 'bg-orange',
                                    'admission' => 'bg-pink',
                                ];
                                $action = strtolower($log->action);
                                $actionColor = $actionColors[$action] ?? 'bg-secondary';
                            @endphp
                            <span class="badge {{ $actionColor }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td>
                            <span data-bs-toggle="tooltip" title="{{ $log->description }}">
                                {{ Str::limit($log->description, 40) }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Success</span>
                            @else
                                <span class="badge bg-danger"><i class="fas fa-times me-1"></i>Failed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No audit logs found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>

<style>
.bg-purple { background-color: #9333ea !important; }
.bg-indigo { background-color: #6610f2 !important; }
.bg-teal { background-color: #20c997 !important; }
.bg-orange { background-color: #fd7e14 !important; }
.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 12px;
}
</style>
@endsection
