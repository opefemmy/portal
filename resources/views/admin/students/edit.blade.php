@extends('layouts.app')

@section('title', 'Edit Student')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-user-edit me-2"></i>Edit Student</h4>
    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Profile
    </a>
</div>

@php
    $user = $student->user;
@endphp

<form method="POST" action="{{ route('admin.students.update', $student) }}">
    @csrf
    @method('PUT')

    {{-- ============================================================
         Personal Information (User table)
         ============================================================ --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                           id="name" name="name"
                           value="{{ old('name', $user?->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                           id="email" name="email"
                           value="{{ old('email', $user?->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                           id="phone" name="phone"
                           value="{{ old('phone', $user?->phone) }}" maxlength="30">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="gender" class="form-label">Gender</label>
                    <select class="form-select @error('gender') is-invalid @enderror"
                            id="gender" name="gender">
                        <option value="">—</option>
                        <option value="male"   {{ old('gender', $user?->gender) === 'male'   ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $user?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                    <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror"
                           id="date_of_birth" name="date_of_birth"
                           value="{{ old('date_of_birth', optional($user?->date_of_birth)->format('Y-m-d')) }}">
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="address" class="form-label">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror"
                           id="address" name="address"
                           value="{{ old('address', $user?->address) }}" maxlength="500">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="next_of_kin" class="form-label">Next of Kin</label>
                    <input type="text" class="form-control @error('next_of_kin') is-invalid @enderror"
                           id="next_of_kin" name="next_of_kin"
                           value="{{ old('next_of_kin', $user?->next_of_kin) }}" maxlength="255">
                    @error('next_of_kin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="next_of_kin_phone" class="form-label">Next of Kin Phone</label>
                    <input type="text" class="form-control @error('next_of_kin_phone') is-invalid @enderror"
                           id="next_of_kin_phone" name="next_of_kin_phone"
                           value="{{ old('next_of_kin_phone', $user?->next_of_kin_phone) }}" maxlength="30">
                    @error('next_of_kin_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Academic Information (Student table)
         ============================================================ --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Academic Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="matric_number" class="form-label">Matric Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('matric_number') is-invalid @enderror"
                           id="matric_number" name="matric_number"
                           value="{{ old('matric_number', $student->matric_number) }}" required>
                    @error('matric_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select @error('status') is-invalid @enderror"
                            id="status" name="status" required>
                        <option value="active"     {{ old('status', $student->status) == 'active'     ? 'selected' : '' }}>Active</option>
                        <option value="graduated"  {{ old('status', $student->status) == 'graduated'  ? 'selected' : '' }}>Graduated</option>
                        <option value="suspended"  {{ old('status', $student->status) == 'suspended'  ? 'selected' : '' }}>Suspended</option>
                        <option value="withdrawn"  {{ old('status', $student->status) == 'withdrawn'  ? 'selected' : '' }}>Withdrawn</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="school_id" class="form-label">School <span class="text-danger">*</span></label>
                    <select class="form-select @error('school_id') is-invalid @enderror"
                            id="school_id" name="school_id" required>
                        <option value="">Select School</option>
                        @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ old('school_id', $student->school_id) == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                        @endforeach
                    </select>
                    @error('school_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                    <select class="form-select @error('department_id') is-invalid @enderror"
                            id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $student->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="programme_id" class="form-label">Programme <span class="text-danger">*</span></label>
                    <select class="form-select @error('programme_id') is-invalid @enderror"
                            id="programme_id" name="programme_id" required>
                        <option value="">Select Programme</option>
                        @foreach($programmes as $prog)
                            <option value="{{ $prog->id }}" {{ old('programme_id', $student->programme_id) == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                        @endforeach
                    </select>
                    @error('programme_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="session_id" class="form-label">Session <span class="text-danger">*</span></label>
                    <select class="form-select @error('session_id') is-invalid @enderror"
                            id="session_id" name="session_id" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" {{ old('session_id', $student->session_id) == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                        @endforeach
                    </select>
                    @error('session_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                    <select class="form-select @error('level') is-invalid @enderror"
                            id="level" name="level" required>
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ (string) old('level', $student->level) === (string) $i ? 'selected' : '' }}>
                                {{ \App\Models\Student::LEVEL_NAMES[$i] ?? "Level $i" }}
                            </option>
                        @endfor
                    </select>
                    @error('level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         Location (Student.state_id / lga_id / nationality_id)
         ============================================================ --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="state_id" class="form-label">State of Origin</label>
                    <select class="form-select @error('state_id') is-invalid @enderror"
                            id="state_id" name="state_id">
                        <option value="">Select State</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" {{ old('state_id', $student->state_id) == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="lga_id" class="form-label">LGA</label>
                    <select class="form-select @error('lga_id') is-invalid @enderror"
                            id="lga_id" name="lga_id">
                        <option value="">Select LGA</option>
                        @foreach($lgas as $lga)
                            <option value="{{ $lga->id }}" {{ old('lga_id', $student->lga_id) == $lga->id ? 'selected' : '' }}>{{ $lga->name }}</option>
                        @endforeach
                    </select>
                    @error('lga_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="nationality_id" class="form-label">Nationality</label>
                    <select class="form-select @error('nationality_id') is-invalid @enderror"
                            id="nationality_id" name="nationality_id">
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nat)
                            <option value="{{ $nat->id }}" {{ old('nationality_id', $student->nationality_id) == $nat->id ? 'selected' : '' }}>{{ $nat->name }}</option>
                        @endforeach
                    </select>
                    @error('nationality_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Update Student
        </button>
        <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">
            <i class="fas fa-times me-2"></i>Cancel
        </a>
    </div>
</form>
@endsection