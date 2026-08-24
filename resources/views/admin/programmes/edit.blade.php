@extends('layouts.app')

@section('title', 'Edit Programme')

@section('content')
<div class="page-header">
    <h4>Edit Programme</h4>
    <p class="text-muted mb-0">{{ $programme->name }} — {{ $programme->code }}</p>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle me-2"></i>
        Please fix the errors below and try again.
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.programmes.update', $programme) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Programme Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           value="{{ old('name', $programme->name) }}"
                           class="form-control @error('name') is-invalid @enderror"
                           required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Code <span class="text-danger">*</span></label>
                    <input type="text" name="code"
                           value="{{ old('code', $programme->code) }}"
                           class="form-control @error('code') is-invalid @enderror"
                           required maxlength="20">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        @foreach(['ND','HND','Degree','PGD','Masters','PhD'] as $type)
                            <option value="{{ $type }}" {{ old('type', $programme->type) === $type ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                        <option value="">— Unassigned —</option>
                        @foreach($departments as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('department_id', $programme->department_id) === (string) $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i>Update Programme
                </button>
                <a href="{{ route('admin.programmes.index') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection