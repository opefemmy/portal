@extends('layouts.app')

@section('title', 'Complete Your Profile')

@section('content')
<div class="page-header">
    <h4>Complete Your Profile</h4>
    <p class="text-muted">Please complete your profile to access all features.</p>
</div>

<!-- Passport Upload -->
<div class="card mb-4">
    <div class="card-header">
        <h5>Passport Photo</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                @php $user = auth()->user(); @endphp
                @if($user->passport)
                    <img src="{{ asset('uploads/passports/' . $user->passport) }}" alt="Passport" class="img-thumbnail" style="max-width: 150px;">
                @else
                    <div class="bg-light d-flex align-items-center justify-content-center" style="width: 150px; height: 150px;">
                        <i class="fas fa-user fa-3x text-muted"></i>
                    </div>
                @endif
            </div>
            <div class="col-md-9">
                <form method="POST" action="{{ route('student.profile.passport') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="passport" class="form-label">Upload Passport (JPEG, PNG, JPG - Max 2MB)</label>
                        <input type="file" name="passport" class="form-control" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Passport
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('student.profile.update') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="matric_number" class="form-label">Matric Number</label>
                        <input type="text" class="form-control @error('matric_number') is-invalid @enderror"
                               id="matric_number" name="matric_number" value="{{ old('matric_number', $student->matric_number) }}">
                        @error('matric_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">School *</label>
                        <select class="form-select @error('school_id') is-invalid @enderror"
                                id="school_id" name="school_id" required>
                            <option value="">Select School</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}" {{ $student->school_id == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department *</label>
                        <select class="form-select @error('department_id') is-invalid @enderror"
                                id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $student->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme *</label>
                        <select class="form-select @error('programme_id') is-invalid @endreif"
                                id="programme_id" name="programme_id" required>
                            <option value="">Select Programme</option>
                            @foreach($programmes as $prog)
                                <option value="{{ $prog->id }}" {{ $student->programme_id == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session *</label>
                        <select class="form-select @error('session_id') is-invalid @ende"
                                id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" {{ $student->session_id == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level *</label>
                        <select class="form-select @error('level') is-invalid @ende"
                                id="level" name="level" required>
                            <option value="">Select Level</option>
                            <option value="1" {{ $student->level == 1 ? 'selected' : '' }}>100L / ND1</option>
                            <option value="2" {{ $student->level == 2 ? 'selected' : '' }}>200L / ND</option>
                            <option value="3" {{ $student->level == 3 ? 'selected' : '' }}>300L / HND1</option>
                            <option value="4" {{ $student->level == 4 ? 'selected' : '' }}>400L / HND2</option>
                            <option value="5" {{ $student->level == 5 ? 'selected' : '' }}>500L</option>
                            <option value="6" {{ $student->level == 6 ? 'selected' : '' }}>600L</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Profile
                </button>
            </div>
        </form>
    </div>
</div>
@endsection