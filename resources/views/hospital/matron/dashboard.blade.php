@extends('layouts.app')

@section('title', 'Matron Dashboard')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-user-nurse me-2"></i>Matron Dashboard</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.dashboard') }}">Hospital</a></li>
                <li class="breadcrumb-item active">Matron</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto">
            <span class="badge bg-primary">{{ now()->format('l, F d, Y') }}</span>
        </div>
    </div>
</div>

<!-- KPIs -->
<div class="row">
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Inpatients</p>
                <h3 class="mb-0">{{ $stats['inpatients'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Today's Admissions</p>
                <h3 class="mb-0">{{ $stats['today_admissions'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Today's Discharges</p>
                <h3 class="mb-0">{{ $stats['today_discharges'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body">
                <p class="text-muted mb-1">Available Beds</p>
                <h3 class="mb-0">{{ $stats['available_beds'] }}</h3>
            </div>
        </div>
    </div>
</div>

<!-- Quick actions -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('hospital.wards.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-procedures me-1"></i> Wards
                </a>
                <a href="{{ route('hospital.matron.rounds') }}" class="btn btn-outline-success">
                    <i class="fas fa-stethoscope me-1"></i> Ward Rounds
                </a>
                <a href="{{ route('hospital.matron.staff') }}" class="btn btn-outline-info">
                    <i class="fas fa-user-clock me-1"></i> Staff Load
                </a>
                <a href="{{ route('hospital.roster.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-calendar-week me-1"></i> Duty Roster
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Ward occupancy -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-procedures me-2"></i>Ward Occupancy</h5>
            </div>
            <div class="card-body">
                @forelse($wards as $ward)
                    @php $pct = $ward->beds_count > 0 ? round(($ward->occupied_beds_count / max($ward->beds_count, 1)) * 100) : 0; @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $ward->name }}</strong>
                            <small class="text-muted">{{ $ward->occupied_beds_count }}/{{ $ward->beds_count }} ({{ $pct }}%)</small>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $pct > 85 ? 'danger' : ($pct > 60 ? 'warning' : 'success') }}"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">No wards configured.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-clock me-2"></i>Upcoming Roster</h5>
            </div>
            <div class="card-body" style="max-height: 360px; overflow-y:auto;">
                @forelse($upcomingRoster as $entry)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <strong>{{ optional($entry->staff)->full_name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $entry->shift ?? '—' }} @ {{ $entry->location ?? '—' }}</small>
                        </div>
                        <div class="text-end">
                            <small>{{ \Carbon\Carbon::parse($entry->duty_date)->format('D d M') }}</small>
                            <br><small>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">No upcoming roster entries.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent admissions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title"><i class="fas fa-user-plus me-2"></i>Recent Admissions</h5>
                <a href="{{ route('hospital.matron.rounds') }}" class="btn btn-sm btn-outline-primary">View Rounds</a>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Ward / Bed</th>
                            <th>Doctor</th>
                            <th>Admitted</th>
                            <th>Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAdmissions as $adm)
                        <tr>
                            <td><strong>{{ optional($adm->patient)->full_name ?? 'Unknown' }}</strong></td>
                            <td>{{ optional($adm->bed->ward)->name }} / Bed {{ optional($adm->bed)->bed_number }}</td>
                            <td>Dr. {{ optional($adm->doctor)->last_name ?? 'TBA' }}</td>
                            <td>{{ optional($adm->admission_date)->format('d M, Y H:i') }}</td>
                            <td>{{ $adm->admission_date->diffInDays(now()) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No active admissions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection