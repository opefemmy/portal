@extends('layouts.app')

@section('title', 'Add School')

@section('content')
<div class="page-header">
    <h4>Add New School</h4>
</div>

<form action="{{ route('admin.schools.store') }}" method="POST">
    @csrf

    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">School Name *</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">School Code *</label>
                <input type="text" name="code" class="form-control" placeholder="e.g., SOC" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Create School
    </button>
    <a href="{{ route('admin.schools.index') }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection