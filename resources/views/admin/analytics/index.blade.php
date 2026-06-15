@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="page-header">
    <h4>Analytics Dashboard</h4>
</div>

<!-- Payment Stats -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stat-card success h-100">
            <div class="card-body">
                <h6 class="text-muted">Total Collected</h6>
                <h2>₦{{ number_format($paymentStats['total_collected'], 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card info h-100">
            <div class="card-body">
                <h6 class="text-muted">This Month</h6>
                <h2>₦{{ number_format($paymentStats['this_month'], 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card warning h-100">
            <div class="card-body">
                <h6 class="text-muted">Pending Payments</h6>
                <h2>₦{{ number_format($paymentStats['pending'], 0) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Enrollment by Level -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Student Enrollment by Level</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Students</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $total = $enrollmentByLevel->sum('count') @endphp
                        @forelse($enrollmentByLevel as $level)
                        <tr>
                            <td>{{ \App\Models\Course::getLevelName($level->level) }}</td>
                            <td>{{ $level->count }}</td>
                            <td>{{ $total > 0 ? round(($level->count / $total) * 100, 1) : 0 }}%</td>
                        </tr>
                        @empty
                        <tr><td colspan="3">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Applications by Status -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Applications by Status</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applicationsByStatus as $app)
                        <tr>
                            <td>
                                <span class="badge bg-{{ $app->status == 'admitted' ? 'success' : ($app->status == 'pending' ? 'warning' : ($app->status == 'rejected' ? 'danger' : 'info') }}">
                                    {{ ucfirst($app->status) }}
                                </span>
                            </td>
                            <td>{{ $app->count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Top Departments -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Top Departments by Enrollment</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topDepartments as $dept)
                        <tr>
                            <td>{{ $dept->department->name ?? 'N/A' }}</td>
                            <td>{{ $dept->count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2">No data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Result Stats -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Result Statistics</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h6>Pass Rate</h6>
                    @php
                        $passRate = $resultStats['total_results'] > 0
                            ? round(($resultStats['passed'] / $resultStats['total_results']) * 100, 1)
                            : 0;
                    @endphp
                    <h2 class="text-success">{{ $passRate }}%</h2>
                </div>
                <table class="table">
                    <tr>
                        <td>Total Results:</td>
                        <td><strong>{{ $resultStats['total_results'] }}</strong></td>
                    </tr>
                    <tr>
                        <td>Passed:</td>
                        <td><span class="text-success">{{ $resultStats['passed'] }}</span></td>
                    </tr>
                    <tr>
                        <td>Failed:</td>
                        <td><span class="text-danger">{{ $resultStats['failed'] }}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection