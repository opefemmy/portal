@extends('layouts.app')

@section('title', 'Business Committee Dashboard')

@section('content')
<div class="page-header">
    <h4>Business Committee Dashboard</h4>
</div>

@include('widgets.render', ['widgets' => $widgets])

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