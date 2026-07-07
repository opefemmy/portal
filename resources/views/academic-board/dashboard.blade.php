@extends('layouts.app')

@section('title', 'Academic Board Dashboard')

@section('content')
<div class="page-header">
    <h4>Academic Board Dashboard</h4>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Pending Final Approval</h6>
                <h2>{{ $pendingResults }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Final Approved Results</h6>
                <h2>{{ $finalApproved }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-gavel me-2"></i>Final Result Approval</h5>
    </div>
    <div class="card-body">
        <p>Final approval for results after Business Committee review. This is the last step in the approval workflow.</p>
        <a href="{{ route('academic-board.results') }}" class="btn btn-primary">
            <i class="fas fa-list me-2"></i>View Results for Final Approval
        </a>
    </div>
</div>
@endsection