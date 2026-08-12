@extends('layouts.app')

@section('title', 'HOD Dashboard')

@section('content')
<div class="page-header">
    <h4>Head of Department Dashboard</h4>
    <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}</p>
</div>

@if(!$departmentId)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    You are not assigned to any department. Please contact the administrator.
</div>
@else
@include('widgets.render', ['widgets' => $widgets])

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('hod.courses.assign') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Assign Course
                    </a>
                    <a href="{{ route('hod.courses') }}" class="btn btn-outline-primary">
                        <i class="fas fa-book me-2"></i>Manage Courses
                    </a>
                    <a href="{{ route('hod.results.index') }}" class="btn btn-outline-warning">
                        <i class="fas fa-clipboard-check me-2"></i>Review Results
                    </a>
                    <a href="{{ route('hod.timetable') }}" class="btn btn-outline-info">
                        <i class="fas fa-calendar-alt me-2"></i>View Timetable
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection