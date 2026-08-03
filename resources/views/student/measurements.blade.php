@extends('layouts.app')

@section('title', 'My Measurements')

@section('content')
@php
$user = auth()->user();
$student = ($user && method_exists($user, 'students')) ? $user->students()->first() : null;
@endphp

<div class="page-header">
    <h4>My Uniform & Lab Coat Measurements</h4>
    <p class="text-muted">View and print your uniform measurements</p>
</div>

@if(!$student)
<div class="alert alert-warning">
    No student record found. Please contact the registry.
</div>
@else
<div class="row">
    <div class="col-md-8">
        <!-- Uniform Section -->
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-tshirt me-2"></i>Uniform</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-shirt fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Shirt Size</strong></p>
                            <h4>{{ $student->uniform_shirt_size ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-user fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Pant Size</strong></p>
                            <h4>{{ $student->uniform_pant_size ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-shoe-prints fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Shoe Size</strong></p>
                            <h4>{{ $student->uniform_shoe_size ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scrubs Section -->
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Scrubs</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-check-square fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Scrub Size</strong></p>
                            <h4>{{ $student->scrub_size ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-palette fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Scrub Color</strong></p>
                            <h4>{{ $student->scrub_color ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lab Coat Section -->
        <div class="card mb-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Lab Coat</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-ruler-vertical fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Lab Coat Size</strong></p>
                            <h4>{{ $student->lab_coat_size ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-ruler fa-2x text-muted mb-2"></i>
                            <p class="mb-1"><strong>Lab Coat Length</strong></p>
                            <h4>{{ $student->lab_coat_length ?? 'Not Set' }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">My Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Matric Number:</strong> {{ $student->matric_number }}</p>
                <p><strong>Name:</strong> {{ $user->name }}</p>
                <p><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}</p>
                <p><strong>Level:</strong> {{ $student->level }}</p>
            </div>
        </div>

        <div class="mt-3">
            <button onclick="window.print()" class="btn btn-primary w-100 mb-2">
                <i class="fas fa-print me-2"></i>Print Measurements
            </button>
        </div>
    </div>
</div>

<!-- Print-only header -->
<div class="d-none d-print-block">
    <div class="text-center mb-4">
        <h2>Student Measurements Slip</h2>
        <p><strong>Name:</strong> {{ $user->name }} | <strong>Matric:</strong> {{ $student->matric_number }}</p>
        <p><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }} | <strong>Level:</strong> {{ $student->level }}</p>
        <hr>
    </div>
</div>
@endif
@endsection
