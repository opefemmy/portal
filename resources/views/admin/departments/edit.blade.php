@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
<div class="page-header">
    <h4>Edit Department</h4>
    <p class="text-muted mb-0">{{ $department->name }} — {{ $department->code }}</p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        Please fix the errors below and try again.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.departments.update', $department) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           value="{{ old('name', $department->name) }}"
                           class="form-control @error('name') is-invalid @enderror"
                           required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code"
                           value="{{ old('code', $department->code) }}"
                           class="form-control @error('code') is-invalid @enderror"
                           required maxlength="20">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">School <span class="text-danger">*</span></label>
                    <select name="school_id" class="form-select @error('school_id') is-invalid @enderror" required>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ (string) old('school_id', $department->school_id) === (string) $school->id ? 'selected' : '' }}>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $department->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_active" value="1" id="is_active"
                               class="form-check-input"
                               {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Department
                </button>
                <a href="{{ route('admin.departments.index') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection