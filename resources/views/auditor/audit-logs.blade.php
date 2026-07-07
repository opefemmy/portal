@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Audit Logs</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-3">
                <select name="module" class="form-select">
                    <option value="">All Modules</option>
                    <option value="hospital">Hospital</option>
                    <option value="finance">Finance</option>
                    <option value="student">Student</option>
                    <option value="staff">Staff</option>
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
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>

        <table class="table datatable">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>User</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $log->user->name ?? 'System' }}</td>
                    <td>{{ ucfirst($log->module) }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ Str::limit($log->description, 50) }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td>
                        <span class="badge bg-{{ $log->status === 'success' ? 'success' : 'danger' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No audit logs found</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{ $logs->links() }}
    </div>
</div>
@endsection