@extends('layouts.app')

@section('title', 'Auditor Dashboard')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Auditor Dashboard</h4>

    {{-- Stat tiles — widget rendered (auditor audience) --}}
    @include('widgets.render', ['widgets' => $widgets])

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
                                <td>{{ optional($log->created_at)->format('d M H:i') ?? 'N/A' }}</td>
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
                                <td>{{ optional($action->created_at)->format('d M H:i') ?? 'N/A' }}</td>
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