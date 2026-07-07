@extends('layouts.app')

@section('title', 'Auditor Dashboard')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Auditor Dashboard</h4>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Transactions</h5>
                    <h2>{{ number_format($stats['total_transactions']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Receipts</h5>
                    <h2>{{ number_format($stats['total_receipts']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Deleted Records</h5>
                    <h2>{{ number_format($stats['total_deleted_records']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Audit Logs</h5>
                    <h2>{{ number_format($stats['audit_logs_count']) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Audit Logs</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M H:i') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td>{{ $log->module }}</td>
                                <td>{{ $log->action }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4">No logs</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Failed Actions</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($failedActions as $action)
                            <tr>
                                <td>{{ $action->created_at->format('d M H:i') }}</td>
                                <td>{{ $action->user->name ?? 'Unknown' }}</td>
                                <td>{{ Str::limit($action->error_message, 50) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3">No failed actions</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection