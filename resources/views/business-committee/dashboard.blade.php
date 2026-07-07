@extends('layouts.app')

@section('title', 'Business Committee Dashboard')

@section('content')
<div class="page-header">
    <h4>Business Committee Dashboard</h4>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Pending Results</h6>
                <h2>{{ $pendingResults }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Approved Results</h6>
                <h2>{{ $approvedResults }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Result Approval</h5>
    </div>
    <div class="card-body">
        <p>Review and approve results that have been passed by the Dean.</p>
        <a href="{{ route('business-committee.results') }}" class="btn btn-primary">
            <i class="fas fa-list me-2"></i>View Results for Approval
        </a>
    </div>
</div>
@endsection