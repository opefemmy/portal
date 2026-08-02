@extends('layouts.app')

@section('title', 'View Course')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Course Details</h4>
    <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th width="30%">Code:</th><td><strong>{{ $course->code }}</strong></td></tr>
            <tr><th>Title:</th><td>{{ $course->title }}</td></tr>
            <tr><th>Units:</th><td>{{ $course->units }}</td></tr>
            <tr><th>Semester:</th><td>{{ ucfirst($course->semester) }}</td></tr>
            <tr><th>Level:</th><td>{{ $course->level }}</td></tr>
            <tr><th>School:</th><td>{{ $course->school->name ?? 'N/A' }}</td></tr>
            <tr><th>Department:</th><td>{{ $course->department->name ?? 'N/A' }}</td></tr>
            <tr><th>Programme:</th><td>{{ $course->programme->name ?? 'N/A' }}</td></tr>
            <tr><th>Description:</th><td>{{ $course->description ?? 'N/A' }}</td></tr>
        </table>
        <a href="{{ route('admin.courses.edit', $course) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
    </div>
</div>
@endsection