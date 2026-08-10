@extends('layouts.app')

@section('title', 'Student Measurements')

@section('content')
<div class="page-header">
    <h4>Student Uniform Measurements</h4>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Student Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Matric Number:</strong> {{ $student->matric_number }}</p>
                <p><strong>Name:</strong> {{ $student->user->name }}</p>
                <p><strong>Email:</strong> {{ $student->user->email }}</p>
                <p><strong>School:</strong> {{ $student->school->name ?? 'N/A' }}</p>
                <p><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}</p>
                <p><strong>Level:</strong> {{ $student->level_display ?? $student->level }}</p>
                <p><strong>Status:</strong>
                    @if($student->status === 'active')
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">{{ ucfirst($student->status) }}</span>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-tshirt me-2"></i>Uniform Measurements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <p><strong>Shirt Size:</strong> {{ $student->uniform_shirt_size ?? 'Not set' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Pant Size:</strong> {{ $student->uniform_pant_size ?? 'Not set' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p><strong>Shoe Size:</strong> {{ $student->uniform_shoe_size ?? 'Not set' }}</p>
                    </div>
                </div>
                @if($student->hasUniformMeasurements())
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Complete</span>
                @else
                    <span class="badge bg-warning">Incomplete</span>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Scrub Measurements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Scrub Size:</strong> {{ $student->scrub_size ?? 'Not set' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Scrub Color:</strong> {{ $student->scrub_color ?? 'Not set' }}</p>
                    </div>
                </div>
                @if($student->hasScrubMeasurements())
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Complete</span>
                @else
                    <span class="badge bg-warning">Incomplete</span>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Lab Coat Measurements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Lab Coat Size:</strong> {{ $student->lab_coat_size ?? 'Not set' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Lab Coat Length:</strong> {{ $student->lab_coat_length ?? 'Not set' }}</p>
                    </div>
                </div>
                @if($student->hasLabCoatMeasurements())
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> Complete</span>
                @else
                    <span class="badge bg-warning">Incomplete</span>
                @endif
            </div>
        </div>

        @if($student->measurements_taken_at)
        <div class="card">
            <div class="card-body">
                <p class="mb-0 text-muted">
                    <small>Measurements taken: {{ $student->measurements_taken_at->format('F j, Y g:i A') }}
                    @if($student->measuredBy)
                        by {{ $student->measuredBy->name }}
                    @endif
                    </small>
                </p>
            </div>
        </div>
        @endif

        <div class="mt-3">
            <a href="{{ route('admin.students.measurements.edit', $student->id) }}" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Edit Measurements
            </a>
            <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Student
            </a>
        </div>
    </div>
</div>
@endsection
