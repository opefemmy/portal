@extends('layouts.app')

@section('title', 'System Report')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-chart-line me-2"></i>System Report</h4>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Version</h5>
            </div>
            <div class="card-body">
                <h3>{{ $report['version']['version'] ?? 'N/A' }}</h3>
                <p class="text-muted">{{ $report['version']['release_name'] ?? 'Development' }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Database</h5>
            </div>
            <div class="card-body">
                <h3>{{ $report['database']['tables_count'] ?? 0 }} Tables</h3>
                <p class="text-muted">{{ $report['database']['size_mb'] ?? 0 }} MB</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">PHP & Laravel</h5>
            </div>
            <div class="card-body">
                <p>PHP: <code>{{ $report['php']['version'] }}</code></p>
                <p>Laravel: <code>{{ $report['laravel']['version'] }}</code></p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Storage Usage</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Directory</th>
                    <th>Size (MB)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['storage'] ?? [] as $dir => $size)
                <tr>
                    <td>{{ $dir }}</td>
                    <td>{{ $size }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Code Statistics</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <h3>{{ $controllers }}</h3>
                <p class="text-muted">Controllers</p>
            </div>
            <div class="col-md-4">
                <h3>{{ $services }}</h3>
                <p class="text-muted">Services</p>
            </div>
            <div class="col-md-4">
                <h3>{{ $models }}</h3>
                <p class="text-muted">Models</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Database Tables ({{ count($tables) }})</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm datatable">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Columns</th>
                    <th>Size (MB)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tables as $table)
                <tr>
                    <td>{{ $table['name'] }}</td>
                    <td>{{ count($table['columns']) }}</td>
                    <td>{{ $table['size'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection