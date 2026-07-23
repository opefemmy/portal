@extends('layouts.app')

@section('title', 'Laboratory Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Laboratory Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Laboratory</li>
            </ul>
        </div>
        <div class="col-auto text-end float-end ms-auto">
            <span class="badge bg-primary">{{ now()->format('l, F d, Y') }}</span>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Pending Requests</p>
                        <h3 class="mb-0">{{ $stats['pending_requests'] }}</h3>
                    </div>
                    <div class="stat-icon bg-warning-light">
                        <i class="fas fa-hourglass-half text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">In Progress</p>
                        <h3 class="mb-0">{{ $stats['in_progress'] }}</h3>
                    </div>
                    <div class="stat-icon bg-primary-light">
                        <i class="fas fa-spinner text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Completed Today</p>
                        <h3 class="mb-0">{{ $stats['completed_today'] }}</h3>
                    </div>
                    <div class="stat-icon bg-success-light">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Tests</p>
                        <h3 class="mb-0">{{ $stats['total_tests'] }}</h3>
                    </div>
                    <div class="stat-icon bg-info-light">
                        <i class="fas fa-flask text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="{{ route('hospital.lab.index') }}" class="btn btn-outline-primary w-100 mb-2">
                            <i class="fas fa-list d-block mb-2"></i> All Requests
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-success w-100 mb-2">
                            <i class="fas fa-vial d-block mb-2"></i> Collect Samples
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-info w-100 mb-2">
                            <i class="fas fa-upload d-block mb-2"></i> Upload Results
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="#" class="btn btn-outline-warning w-100 mb-2">
                            <i class="fas fa-history d-block mb-2"></i> Test History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Requests -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title"><i class="fas fa-flask me-2"></i>Pending Lab Requests</h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-warning">{{ $pendingRequests->count() }} pending</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Lab No.</th>
                                <th>Patient</th>
                                <th>Test Type</th>
                                <th>Doctor</th>
                                <th>Requested</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingRequests as $request)
                            <tr>
                                <td><code>{{ $request->lab_number }}</code></td>
                                <td>
                                    <strong>{{ $request->patient->full_name ?? 'Unknown' }}</strong>
                                    <br><small class="text-muted">{{ $request->patient->patient_number ?? '' }}</small>
                                </td>
                                <td>{{ $request->test_type }}</td>
                                <td>Dr. {{ $request->doctor->last_name ?? 'TBA' }}</td>
                                <td>{{ $request->requested_at->format('d M, h:i A') }}</td>
                                <td>
                                    @switch($request->status)
                                        @case('pending')
                                            <span class="badge bg-warning">Pending</span>
                                            @break
                                        @case('sample_collected')
                                            <span class="badge bg-info">Sample Collected</span>
                                            @break
                                        @case('in_progress')
                                            <span class="badge bg-primary">Processing</span>
                                            @break
                                        @case('completed')
                                            <span class="badge bg-success">Completed</span>
                                            @break
                                        @case('cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">{{ ucfirst($request->status) }}</span>
                                    @endswitch
                                </td>
                                <td>
                                    <div class="btn-group">
                                        @if($request->status === 'pending')
                                        <form action="{{ route('hospital.lab.collect', $request->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Collect Sample">
                                                <i class="fas fa-vial"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @if($request->status === 'sample_collected')
                                        <form action="{{ route('hospital.lab.process', $request->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" title="Start Processing">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                        @endif
                                        @if($request->status === 'in_progress')
                                        <a href="#" class="btn btn-sm btn-info" title="Record Results">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                        @endif
                                        <a href="{{ route('hospital.lab.show', $request->id) }}" class="btn btn-sm btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                    No pending lab requests
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
.stat-card .card-body { padding: 1.25rem; }
.stat-icon {
    width: 48px; height: 48px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.bg-primary-light { background-color: rgba(74, 108, 247, 0.1); }
.bg-success-light { background-color: rgba(34, 197, 94, 0.1); }
.bg-warning-light { background-color: rgba(245, 158, 11, 0.1); }
.bg-info-light { background-color: rgba(6, 182, 212, 0.1); }
</style>
@endsection
