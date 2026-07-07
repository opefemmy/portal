@extends('layouts.app')

@section('title', 'Create Fee')

@section('content')
<div class="page-header">
    <h4>Create New Fee</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.fees.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Fee Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="payment_type" class="form-label">Payment Type</label>
                        <select class="form-select @error('payment_type') is-invalid @enderror"
                                id="payment_type" name="payment_type" required>
                            <option value="">Select Payment Type</option>
                            <option value="Tuition Fee">Tuition Fee</option>
                            <option value="Departmental Fee">Departmental Fee</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('payment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (₦)</label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror"
                               id="amount" name="amount" value="{{ old('amount') }}" required min="0" step="0.01">
                        @error('amount')
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
                                <option value="{{ $session->id }}">{{ $session->name }} - {{ $session->semester }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">School (Optional)</label>
                        <select class="form-select @error('school_id') is-invalid @enderror"
                                id="school_id" name="school_id">
                            <option value="">All Schools</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department (Optional)</label>
                        <select class="form-select @error('department_id') is-invalid @enderror"
                                id="department_id" name="department_id">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme (Optional)</label>
                        <select class="form-select @error('programme_id') is-invalid @enderror"
                                id="programme_id" name="programme_id">
                            <option value="">All Programmes</option>
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level (Optional)</label>
                        <select class="form-select @error('level') is-invalid @enderror"
                                id="level" name="level">
                            <option value="">All Levels</option>
                            <option value="1">100L / ND1</option>
                            <option value="2">200L / ND</option>
                            <option value="3">300L / HND1</option>
                            <option value="4">400L / HND2</option>
                            <option value="5">500L</option>
                            <option value="6">600L</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                               id="due_date" name="due_date" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Fee
                </button>
                <a href="{{ route('admin.fees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection