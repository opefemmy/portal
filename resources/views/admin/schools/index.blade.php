@extends('layouts.app')

@section('title', 'Schools')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Schools</h4>
    <a href="{{ route('admin.schools.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add School
    </a>
</div>

<div class="row">
    @forelse($schools as $school)
    <div class="col-md-6 col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5>{{ $school->name }}</h5>
                <p class="text-muted">{{ $school->code }}</p>
                <p>{{ $school->departments->count() }} Departments</p>
                <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit this school">Edit</a>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <p class="text-center">No schools found.</p>
    </div>
    @endforelse
</div>
@endsection