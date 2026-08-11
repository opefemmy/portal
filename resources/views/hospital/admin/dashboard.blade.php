@extends('layouts.app')

@section('title', 'Hospital Admin')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-hospital me-2"></i>Hospital Admin</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Admin</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto">
            <span class="badge bg-primary">{{ now()->format('l, F d, Y') }}</span>
        </div>
    </div>
</div>

<!-- KPI row -->
<div class="row">
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Today's Appointments</p><h3>{{ $stats['today_appointments'] }}</h3>
            <small class="text-muted">{{ $stats['pending_appointments'] }} pending</small>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Inpatients</p><h3>{{ $stats['inpatients'] }}</h3>
            <small class="text-muted">{{ $stats['available_beds'] }} beds available</small>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Revenue Today</p><h3>₦{{ number_format($stats['revenue_today'], 0) }}</h3>
            <small class="text-muted">Month: ₦{{ number_format($stats['revenue_month'], 0) }}</small>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Staff On-Call</p><h3>{{ $stats['staff_available'] }} / {{ $stats['total_staff'] }}</h3>
            <small class="text-muted">{{ $stats['new_patients_today'] }} new patients</small>
        </div></div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Pending Prescriptions</p><h3>{{ $stats['pending_prescriptions'] }}</h3>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Pending Lab</p><h3>{{ $stats['pending_lab'] }}</h3>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Low-Stock Items</p><h3 class="text-{{ $stats['low_stock_items'] > 0 ? 'danger' : 'success' }}">{{ $stats['low_stock_items'] }}</h3>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Total Patients</p><h3>{{ $stats['total_patients'] }}</h3>
    </div></div></div>
</div>

<!-- Quick links -->
<div class="row mb-3 mt-2">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('hospital.admin.staff') }}" class="btn btn-outline-primary"><i class="fas fa-user-md me-1"></i> Staff</a>
                <a href="{{ route('hospital.admin.revenue') }}" class="btn btn-outline-success"><i class="fas fa-coins me-1"></i> Revenue</a>
                <a href="{{ route('hospital.admin.inventory') }}" class="btn btn-outline-warning"><i class="fas fa-pills me-1"></i> Inventory</a>
                <a href="{{ route('hospital.admin.attendance') }}" class="btn btn-outline-info"><i class="fas fa-calendar-check me-1"></i> Attendance</a>
                <a href="{{ route('hospital.patients.index') }}" class="btn btn-outline-secondary"><i class="fas fa-users me-1"></i> Patients</a>
                <a href="{{ route('hospital.appointments.index') }}" class="btn btn-outline-secondary"><i class="fas fa-calendar me-1"></i> Appointments</a>
            </div>
        </div>
    </div>
</div>

<!-- Revenue sparkline + recent admissions -->
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Revenue — last 14 days</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Day</th><th class="text-end">Total</th><th>Bar</th></tr></thead>
                    <tbody>
                        @php $max = $revenueByDay->max() ?: 1; @endphp
                        @forelse($revenueByDay as $day => $total)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($day)->format('D d M') }}</td>
                                <td class="text-end">₦{{ number_format($total, 0) }}</td>
                                <td>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar bg-success" style="width: {{ ($total / $max) * 100 }}%"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center">No payments in the window.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="fas fa-procedures me-2"></i>Recent Admissions</h5></div>
            <div class="card-body" style="max-height: 400px; overflow-y:auto;">
                @forelse($recentAdmissions as $adm)
                    <div class="border-bottom py-2">
                        <strong>{{ optional($adm->patient)->full_name }}</strong>
                        <br><small class="text-muted">
                            {{ optional($adm->bed->ward)->name }} / Bed {{ optional($adm->bed)->bed_number }}
                            — Dr. {{ optional($adm->doctor)->last_name }}
                        </small>
                        <br><small class="text-muted">{{ $adm->admission_date->diffForHumans() }}</small>
                    </div>
                @empty
                    <p class="text-muted text-center">No recent admissions.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection