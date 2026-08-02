@extends('layouts.app')

@section('title', 'View School')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>School Details</h4>
    <a href="{{ route('admin.schools.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th width="30%">Code:</th><td><code>{{ $school->code }}</code></td></tr>
            <tr><th>Name:</th><td>{{ $school->name }}</td></tr>
            <tr><th>Departments:</th><td>{{ $school->departments->count() ?? 0 }}</td></tr>
            <tr><th>Created:</th><td>{{ optional($school->created_at)->format('d M Y') ?? 'N/A' }}</td></tr>
        </table>
        <a href="{{ route('admin.schools.edit', $school) }}" class="btn btn-primary">
            <i class="fas fa-edit me-2"></i>Edit
        </a>
    </div>
</div>
@endsection