@extends('layouts.app')

@section('title', 'Application Statistics')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Application Statistics</h4>
        <p class="text-muted mb-0">Overview of applications across the institution</p>
    </div>
    <div>
        <a href="{{ route('registrar.applications.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Applications
        </a>
    </div>
</div>

{{-- Status overview cards --}}
<div class="row mb-4">
    <div class="col-md-6 col-xl-2 mb-3">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total</h6>
                <h2 class="mb-0">{{ number_format($stats['total'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2 mb-3">
        <div class="card stat-card h-100 border-warning" style="border-left: 4px solid #ffc107;">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Pending</h6>
                <h2 class="mb-0 text-warning">{{ number_format($stats['pending'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2 mb-3">
        <div class="card stat-card h-100 border-info" style="border-left: 4px solid #0dcaf0;">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Screening</h6>
                <h2 class="mb-0 text-info">{{ number_format($stats['screening'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2 mb-3">
        <div class="card stat-card h-100 border-primary" style="border-left: 4px solid #0d6efd;">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Approved</h6>
                <h2 class="mb-0 text-primary">{{ number_format($stats['approved'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2 mb-3">
        <div class="card stat-card h-100 border-success" style="border-left: 4px solid #198754;">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Admitted</h6>
                <h2 class="mb-0 text-success">{{ number_format($stats['admitted'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-2 mb-3">
        <div class="card stat-card h-100 border-danger" style="border-left: 4px solid #dc3545;">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Rejected</h6>
                <h2 class="mb-0 text-danger">{{ number_format($stats['rejected'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
</div>

{{-- Distribution tables --}}
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-university me-2"></i>By School</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>School</th>
                                <th class="text-end">Applications</th>
                                <th class="text-end">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bySchool as $row)
                            <tr>
                                <td>{{ $row->school->name ?? 'Unassigned' }}</td>
                                <td class="text-end">{{ number_format($row->count) }}</td>
                                <td class="text-end">
                                    @if(($stats['total'] ?? 0) > 0)
                                        {{ number_format(($row->count / $stats['total']) * 100, 1) }}%
                                    @else 0% @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No applications yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-building me-2"></i>By Department</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-end">Applications</th>
                                <th class="text-end">% of Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byDepartment as $row)
                            <tr>
                                <td>{{ $row->department->name ?? 'Unassigned' }}</td>
                                <td class="text-end">{{ number_format($row->count) }}</td>
                                <td class="text-end">
                                    @if(($stats['total'] ?? 0) > 0)
                                        {{ number_format(($row->count / $stats['total']) * 100, 1) }}%
                                    @else 0% @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No applications yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Status distribution bars --}}
@php
    $statusRows = [
        ['label' => 'Pending', 'count' => $stats['pending'] ?? 0, 'color' => 'warning'],
        ['label' => 'Screening', 'count' => $stats['screening'] ?? 0, 'color' => 'info'],
        ['label' => 'Approved', 'count' => $stats['approved'] ?? 0, 'color' => 'primary'],
        ['label' => 'Admitted', 'count' => $stats['admitted'] ?? 0, 'color' => 'success'],
        ['label' => 'Rejected', 'count' => $stats['rejected'] ?? 0, 'color' => 'danger'],
    ];
    $totalStat = $stats['total'] ?? 0;
@endphp
<div class="card mt-2">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Status Distribution</h5>
    </div>
    <div class="card-body">
        @foreach($statusRows as $row)
            @php
                $pct = $totalStat > 0 ? ($row['count'] / $totalStat) * 100 : 0;
            @endphp
            <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span><strong>{{ $row['label'] }}</strong></span>
                    <span class="text-muted">{{ number_format($row['count']) }} ({{ number_format($pct, 1) }}%)</span>
                </div>
                <div class="progress" style="height: 18px;">
                    <div class="progress-bar bg-{{ $row['color'] }}" role="progressbar" style="width: {{ $pct }}%;" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
