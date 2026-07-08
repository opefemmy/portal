@extends('layouts.app')

@section('title', 'System Maintenance Dashboard')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-tools me-2"></i>System Maintenance Dashboard</h4>
</div>

<div class="row">
    {{-- Version Info --}}
    <div class="col-md-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-tag me-2"></i>System Version</h5>
            </div>
            <div class="card-body text-center">
                <h2 class="text-primary">{{ $version->version ?? 'Not Set' }}</h2>
                <p class="text-muted">{{ $version->release_name ?? 'Development Build' }}</p>
                <small>Installed: {{ $version->installed_at ?? 'N/A' }}</small>
            </div>
        </div>
    </div>

    {{-- Health Summary --}}
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>System Health</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h3 class="text-success">{{ count(array_filter($health, fn($h) => $h['status'] === 'healthy')) }}</h3>
                        <small class="text-muted">Healthy</small>
                    </div>
                    <div class="col-4">
                        <h3 class="text-warning">{{ count(array_filter($health, fn($h) => $h['status'] === 'warning')) }}</h3>
                        <small class="text-muted">Warning</small>
                    </div>
                    <div class="col-4">
                        <h3 class="text-danger">{{ count(array_filter($health, fn($h) => $h['status'] === 'critical')) }}</h3>
                        <small class="text-muted">Critical</small>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.maintenance.health') }}" class="btn btn-sm btn-outline-primary w-100">
                    View Details
                </a>
            </div>
        </div>
    </div>

    {{-- Pending Updates --}}
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i>Pending Updates</h5>
            </div>
            <div class="card-body text-center">
                <h2 class="text-warning">{{ count($pendingMigrations) }}</h2>
                <p class="text-muted">Migrations Pending</p>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.maintenance.updates') }}" class="btn btn-sm btn-outline-warning w-100">
                    View Updates
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Quick Actions --}}
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.maintenance.health') }}" class="btn btn-outline-primary w-100">
                            <i class="fas fa-heartbeat me-2"></i>Health Check
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('admin.maintenance.updates') }}" class="btn btn-outline-success w-100">
                            <i class="fas fa-sync me-2"></i>Run Updates
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <form method="POST" action="{{ route('admin.maintenance.cache.clear') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-info w-100">
                                <i class="fas fa-broom me-2"></i>Clear Cache
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3 mb-3">
                        <form method="POST" action="{{ route('admin.maintenance.optimize') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-dark w-100">
                                <i class="fas fa-rocket me-2"></i>Optimize
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Backups --}}
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Recent Backups</h5>
                <a href="{{ route('admin.maintenance.backups') }}" class="btn btn-sm btn-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                @if(count($backups) > 0)
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Size</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($backups, 0, 5) as $backup)
                        <tr>
                            <td>{{ $backup['name'] }}</td>
                            <td>{{ ucfirst($backup['type']) }}</td>
                            <td>
                                <span class="badge bg-{{ $backup['status'] === 'completed' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning') }}">
                                    {{ $backup['status'] }}
                                </span>
                            </td>
                            <td>{{ $backup['file_size'] ?? 'N/A' }}</td>
                            <td>{{ $backup['created_at'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted text-center">No backups yet</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Navigation Cards --}}
<div class="row mt-4">
    <div class="col-md-2">
        <a href="{{ route('admin.maintenance.migrations') }}" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-database fa-2x text-primary mb-2"></i>
                <h6>Migrations</h6>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('admin.maintenance.database') }}" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-server fa-2x text-success mb-2"></i>
                <h6>Database</h6>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('admin.maintenance.modules') }}" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-cubes fa-2x text-warning mb-2"></i>
                <h6>Modules</h6>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('admin.maintenance.permissions') }}" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-shield-alt fa-2x text-info mb-2"></i>
                <h6>Permissions</h6>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('admin.maintenance.storage') }}" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-folder fa-2x text-secondary mb-2"></i>
                <h6>Storage</h6>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <a href="{{ route('admin.maintenance.report') }}" class="card text-decoration-none">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x text-dark mb-2"></i>
                <h6>Report</h6>
            </div>
        </a>
    </div>
</div>
@endsection