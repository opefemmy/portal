@extends('layouts.app')

@section('title', 'Occupancy Report')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-chart-bar me-2"></i>Occupancy Report</h4>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hospital.wards.index') }}">Wards</a></li>
        <li class="breadcrumb-item active">Occupancy</li>
    </ul>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Total Beds</p><h3>{{ $totalBeds }}</h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Occupied</p><h3>{{ $occupiedBeds }}</h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Available</p><h3>{{ $availableBeds }}</h3>
        </div></div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card"><div class="card-body">
            <p class="text-muted mb-1">Avg. Stay</p><h3>{{ $avgStay ? round($avgStay) : 0 }} <small>d</small></h3>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title"><i class="fas fa-procedures me-2"></i>Per-Ward Breakdown</h5></div>
    <div class="card-body">
        <table class="table table-hover">
            <thead><tr>
                <th>Ward</th><th>Type</th><th class="text-end">Total</th>
                <th class="text-end">Occupied</th><th class="text-end">Available</th><th>%</th>
            </tr></thead>
            <tbody>
                @foreach($wards as $w)
                    @php $pct = $w->beds_count > 0 ? round(($w->occupied_beds_count / max($w->beds_count, 1)) * 100) : 0; @endphp
                    <tr>
                        <td><strong>{{ $w->name }}</strong></td>
                        <td>{{ ucfirst($w->type) }}</td>
                        <td class="text-end">{{ $w->beds_count }}</td>
                        <td class="text-end">{{ $w->occupied_beds_count }}</td>
                        <td class="text-end">{{ $w->available_beds_count }}</td>
                        <td>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $pct > 85 ? 'danger' : ($pct > 60 ? 'warning' : 'success') }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                            <small>{{ $pct }}%</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h5 class="card-title"><i class="fas fa-sign-out-alt me-2"></i>Discharges — last 30 days</h5></div>
    <div class="card-body">
        <table class="table table-sm">
            <thead><tr><th>Date</th><th class="text-end">Discharges</th></tr></thead>
            <tbody>
                @forelse($dischargesByDay as $day)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($day->day)->format('D d M Y') }}</td>
                        <td class="text-end">{{ $day->total }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted text-center">No discharges in the last 30 days.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection