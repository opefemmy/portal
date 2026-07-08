@extends('layouts.app')

@section('title', 'System Health Check')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-heartbeat me-2"></i>System Health Check</h4>
    <form method="POST" action="{{ route('admin.maintenance.health.run') }}">
        @csrf
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sync me-2"></i>Re-run Check
        </button>
    </form>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    @foreach($results as $check)
    <div class="col-md-6 mb-3">
        <div class="card border-{{ $check['status'] === 'healthy' ? 'success' : ($check['status'] === 'warning' ? 'warning' : 'danger') }}">
            <div class="card-header bg-{{ $check['status'] === 'healthy' ? 'success' : ($check['status'] === 'warning' ? 'warning' : 'danger') }} text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $check['name'] }}</h5>
                <span class="badge bg-light text-dark">{{ ucfirst($check['status']) }}</span>
            </div>
            <div class="card-body">
                <p class="mb-2">{{ $check['message'] }}</p>

                @if(isset($check['details']))
                <small class="text-muted">
                    @foreach($check['details'] as $key => $value)
                    <span class="me-3">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</span>
                    @endforeach
                </small>
                @endif

                @if($check['status'] !== 'healthy')
                <form method="POST" action="{{ route('admin.maintenance.health.repair') }}" class="mt-2">
                    @csrf
                    <input type="hidden" name="check_name" value="{{ strtolower(str_replace(' ', '_', $check['name'])) }}">
                    <button type="submit" class="btn btn-sm btn-outline-{{ $check['status'] === 'critical' ? 'danger' : 'warning' }}">
                        <i class="fas fa-wrench me-1"></i>Repair
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Health Summary</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h2>{{ count(array_filter($results, fn($r) => $r['status'] === 'healthy')) }}</h2>
                        <p class="mb-0">Healthy Checks</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h2>{{ count(array_filter($results, fn($r) => $r['status'] === 'warning')) }}</h2>
                        <p class="mb-0">Warnings</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h2>{{ count(array_filter($results, fn($r) => $r['status'] === 'critical')) }}</h2>
                        <p class="mb-0">Critical Issues</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection