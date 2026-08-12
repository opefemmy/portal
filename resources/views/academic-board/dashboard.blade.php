@extends('layouts.app')

@section('title', 'Academic Board Dashboard')

@section('content')
<div class="page-header">
    <h4>Academic Board Dashboard</h4>
</div>

@include('widgets.render', ['widgets' => $widgets])

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