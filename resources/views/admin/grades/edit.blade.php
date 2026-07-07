@extends('layouts.app')

@section('title', 'Edit Grade')

@section('content')
<div class="page-header">
    <h4>Edit Grade</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.grades.update', $grade->id) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="min_score" class="form-label">Minimum Score</label>
                        <input type="number" class="form-control @error('min_score') is-invalid @enderror"
                               id="min_score" name="min_score" value="{{ old('min_score', $grade->min_score) }}" required min="0" max="100">
                        @error('min_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="max_score" class="form-label">Maximum Score</label>
                        <input type="number" class="form-control @error('max_score') is-invalid @enderror"
                               id="max_score" name="max_score" value="{{ old('max_score', $grade->max_score) }}" required min="0" max="100">
                        @error('max_score')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="grade" class="form-label">Grade Letter</label>
                        <input type="text" class="form-control @error('grade') is-invalid @enderror"
                               id="grade" name="grade" value="{{ old('grade', $grade->grade) }}" required maxlength="5">
                        @error('grade')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="grade_point" class="form-label">Grade Point</label>
                        <input type="number" class="form-control @error('grade_point') is-invalid @enderror"
                               id="grade_point" name="grade_point" value="{{ old('grade_point', $grade->grade_point) }}" required min="0" max="5" step="0.1">
                        @error('grade_point')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="gpa_weight" class="form-label">GPA Weight</label>
                        <input type="number" class="form-control @error('gpa_weight') is-invalid @enderror"
                               id="gpa_weight" name="gpa_weight" value="{{ old('gpa_weight', $grade->gpa_weight) }}" min="0" max="5">
                        @error('gpa_weight')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="remark" class="form-label">Remark</label>
                <input type="text" class="form-control @error('remark') is-invalid @enderror"
                       id="remark" name="remark" value="{{ old('remark', $grade->remark) }}" required>
                @error('remark')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="classification" class="form-label">Classification</label>
                <select class="form-select @error('classification') is-invalid @enderror"
                        id="classification" name="classification">
                    <option value="">Select Classification</option>
                    <option value="first_class" {{ old('classification', $grade->classification) == 'first_class' ? 'selected' : '' }}>First Class Honours</option>
                    <option value="second_class_upper" {{ old('classification', $grade->classification) == 'second_class_upper' ? 'selected' : '' }}>Second Class Upper</option>
                    <option value="second_class_lower" {{ old('classification', $grade->classification) == 'second_class_lower' ? 'selected' : '' }}>Second Class Lower</option>
                    <option value="third_class" {{ old('classification', $grade->classification) == 'third_class' ? 'selected' : '' }}>Third Class</option>
                    <option value="pass" {{ old('classification', $grade->classification) == 'pass' ? 'selected' : '' }}>Pass</option>
                    <option value="fail" {{ old('classification', $grade->classification) == 'fail' ? 'selected' : '' }}>Fail</option>
                </select>
                @error('classification')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Grade
                </button>
                <a href="{{ route('admin.grades.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection