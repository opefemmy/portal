@extends('layouts.app')

@section('title', 'Application Statistics')

@section('content')
<div class="page-header">
    <h4>Application Statistics</h4>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                <h5>Total Applications</h5>
                <h3>{{ $stats['total'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h5>Approved</h5>
                <h3>{{ $stats['approved'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i>
                <h5>Pending</h5>
                <h3>{{ $stats['pending'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-times-circle fa-2x text-danger mb-2"></i>
                <h5>Rejected</h5>
                <h3>{{ $stats['rejected'] ?? 0 }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>By School</h5>
            </div>
            <div class="card-body">
                @if(isset($bySchool) && count($bySchool) > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr><th>School</th><th>Count</th></tr>
                        </thead>
                        <tbody>
                            @foreach($bySchool as $row)
                                <tr>
                                    <td>{{ $row->school_name ?? $row->name ?? 'N/A' }}</td>
                                    <td>{{ $row->count ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">No data available.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>By Department</h5>
            </div>
            <div class="card-body">
                @if(isset($byDept) && count($byDept) > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Department</th><th>Count</th></tr>
                        </thead>
                        <tbody>
                            @foreach($byDept as $row)
                                <tr>
                                    <td>{{ $row->department_name ?? $row->name ?? 'N/A' }}</td>
                                    <td>{{ $row->count ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">No data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>By Status</h5>
            </div>
            <div class="card-body">
                @if(isset($byStatus) && count($byStatus) > 0)
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Status</th><th>Count</th></tr>
                        </thead>
                        <tbody>
                            @foreach($byStatus as $row)
                                <tr>
                                    <td>{{ $row->status ?? 'N/A' }}</td>
                                    <td>{{ $row->count ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted">No data available.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
