@extends('layouts.app')

@section('title', 'Add Student')

@section('content')
<div class="page-header">
    <h4>Add New Student</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.students.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="matric_number" class="form-label">Matric Number</label>
                        <input type="text" class="form-control @error('matric_number') is-invalid @enderror"
                               id="matric_number" name="matric_number" value="{{ old('matric_number') }}" required>
                        @error('matric_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">Password (default: password123)</label>
                        <input type="password" class="form-control"
                               id="password" name="password">
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
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select @error('department_id') is-invalid @enderror"
                                id="department_id" name="department_id" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
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
                                <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session</label>
                        <select class="form-select @error('session_id') is-invalid @enderror"
                                id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select class="form-select @error('level') is-invalid @enderror"
                                id="level" name="level" required>
                            <option value="">Select Level</option>
                            <option value="1">ND1 (100L)</option>
                            <option value="2">ND (200L)</option>
                            <option value="3">HND1 (300L)</option>
                            <option value="4">HND2 (400L)</option>
                            <option value="5">500L</option>
                            <option value="6">600L</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <h5 class="mt-4 mb-3">Location Information</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="state_id" class="form-label">State of Origin</label>
                        <select class="form-select @error('state_id') is-invalid @enderror"
                                id="state_id" name="state_id">
                            <option value="">Select State</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}">{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="lga_id" class="form-label">Local Government Area</label>
                        <select class="form-select" id="lga_id" name="lga_id">
                            <option value="">Select LGA</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="nationality_id" class="form-label">Nationality</label>
                        <select class="form-select" id="nationality_id" name="nationality_id">
                            <option value="">Select Nationality</option>
                            @foreach($nationalities as $nationality)
                                <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Student
                </button>
                <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
$('#state_id').change(function() {
    var stateId = $(this).val();
    $('#lga_id').empty().append('<option value="">Select LGA</option>');
    if(stateId) {
        $.get('/admin/students/lgas/' + stateId, function(data) {
            $.each(data, function(index, lga) {
                $('#lga_id').append('<option value="'+lga.id+'">'+lga.name+'</option>');
            });
        });
    }
});
</script>
@endpush
@endsection