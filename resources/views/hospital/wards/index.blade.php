@extends('layouts.app')

@section('title', 'Wards')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-procedures me-2"></i>Wards</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.matron.dashboard') }}">Matron</a></li>
                <li class="breadcrumb-item active">Wards</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto">
            <a href="{{ route('hospital.wards.occupancy') }}" class="btn btn-outline-info me-2">
                <i class="fas fa-chart-bar me-1"></i> Occupancy Report
            </a>
            @can('wards.manage')
            <a href="{{ route('hospital.wards.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> New Ward
            </a>
            @endcan
        </div>
    </div>
</div>

<div class="row">
    @forelse($wards as $ward)
        @php $pct = $ward->beds_count > 0 ? round(($ward->occupied_beds_count / max($ward->beds_count, 1)) * 100) : 0; @endphp
        <div class="col-md-4 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="mb-0">{{ $ward->name }}</h5>
                        <span class="badge bg-secondary">{{ ucfirst($ward->type) }}</span>
                    </div>
                    <p class="text-muted mb-2">{{ \Illuminate\Support\Str::limit($ward->description, 80) }}</p>
                    <div class="d-flex justify-content-between">
                        <small>Available</small>
                        <strong>{{ $ward->available_beds_count }} / {{ $ward->beds_count }}</strong>
                    </div>
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar bg-{{ $pct > 85 ? 'danger' : ($pct > 60 ? 'warning' : 'success') }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    <small class="text-muted">Daily rate: ₦{{ number_format($ward->daily_rate, 2) }}</small>
                    <div class="mt-2 d-flex gap-2">
                        <a href="{{ route('hospital.wards.beds', $ward) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-bed"></i> Beds
                        </a>
                        @can('wards.manage')
                        <a href="{{ route('hospital.wards.edit', $ward) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-info text-center">No wards yet.</div>
        </div>
    @endforelse
</div>

<div class="mt-3">
    {{ $wards->links() }}
</div>
@endsection