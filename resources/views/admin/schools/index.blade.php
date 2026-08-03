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
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.schools.show', $school) }}"
                       class="btn btn-sm btn-outline-info"
                       title="View school details">
                        <i class="fas fa-eye me-1"></i>View
                    </a>
                    <a href="{{ route('admin.schools.edit', $school) }}"
                       class="btn btn-sm btn-outline-primary"
                       title="Edit this school's name, code, or description">
                        <i class="fas fa-edit me-1"></i>Edit
                    </a>
                    <form method="POST" action="{{ route('admin.schools.destroy', $school) }}"
                          class="d-inline"
                          onsubmit="return confirm('Delete school {{ addslashes($school->name) }}? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="btn btn-sm btn-outline-danger"
                                title="Delete this school. Blocked if any departments, students, courses, or fees still reference it.">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </form>
                </div>
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