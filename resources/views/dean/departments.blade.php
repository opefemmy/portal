@extends('layouts.app')

@section('title', 'Departments')

@section('content')
@php
    $user = auth()->user();
    $schoolId = $user?->school_id;
@endphp

<div class="page-header">
    <h4>Departments in My School</h4>
    <p class="text-muted mb-0">All academic departments under your school, with head counts.</p>
</div>

@if(!$schoolId)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        You are not assigned to any school. Please contact the administrator.
    </div>
@else
    @php
        $departments = \App\Models\Department::where('school_id', $schoolId)
            ->withCount(['programmes', 'students'])
            ->orderBy('name')
            ->get();
    @endphp

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $departments->count() }} department{{ $departments->count() === 1 ? '' : 's' }}</h5>
        </div>
        <div class="card-body">
            @if($departments->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    No departments have been created in your school yet.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Code</th>
                                <th>Programmes</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($departments as $dept)
                                <tr>
                                    <td>{{ $dept->name }}</td>
                                    <td><code>{{ $dept->code ?? '—' }}</code></td>
                                    <td>
                                        <a href="{{ route('dean.students', ['department_id' => $dept->id]) }}"
                                           class="text-decoration-none">
                                            {{ $dept->programmes_count }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('dean.students', ['department_id' => $dept->id]) }}"
                                           class="text-decoration-none">
                                            {{ $dept->students_count }}
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif
@endsection