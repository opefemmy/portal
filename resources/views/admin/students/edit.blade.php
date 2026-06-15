@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
<div class="page-header">
    <h4>Edit Student</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.students.update', $student) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="matric_number" class="form-label">Matric Number</label>
                        <input type="text" class="form-control @error('matric_number') is-invalid @enderror"
                               id="matric_number" name="matric_number" value="{{ old('matric_number', $student->matric_number) }}" required>
                        @error('matric_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select @error('status') is-invalid @enderror"
                                id="status" name="status" required>
                            <option value="active" {{ $student->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="graduated" {{ $student->status == 'graduated' ? 'selected' : '' }}>Graduated</option>
                            <option value="suspended" {{ $student->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                            <option value="withdrawn" {{ $student->status == 'withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">School</label>
                        <select class="form-select @error('school_id') is-invalid @enderror"
                                id="school_id" name="school_id" required>
                            <option value="">Select School</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ old('school_id', $student->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select @error('department_id') is-invalid @enderror"
                                id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $student->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme</label>
                        <select class="form-select @error('programme_id') is-invalid @enderror"
                                id="programme_id" name="programme_id" required>
                            <option value="">Select Programme</option>
                            @foreach($programmes as $prog)
                                <option value="{{ $prog->id }}" {{ old('programme_id', $student->programme_id) == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session</label>
                        <select class="form-select @error('session_id') is-invalid @endre"
                                id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" {{ old('session_id', $student->session_id) == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select class="form-select @error('level') is-invalid @enderror"
                                id="level" name="level" required>
                            <option value="1" {{ old('level', $student->level) == '1' ? 'selected' : '' }}>ND1 (100L)</option>
                            <option value="2" {{ old('level', $student->level) == '2' ? 'selected' : '' }}>ND (200L)</option>
                            <option value="3" {{ old('level', $student->level) == '3' ? 'selected' : '' }}>HND1 (300L)</option>
                            <option value="4" {{ old('level', $student->level) == '4' ? 'selected' : '' }}>HND2 (400L)</option>
                            <option value="5" {{ old('level', $student->level) == '5' ? 'selected' : '' }}>500L</option>
                            <option value="6" {{ old('level', $student->level) == '6' ? 'selected' : '' }}>600L</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update Student
                </button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection