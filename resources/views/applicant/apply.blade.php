@extends('layouts.app')

@section('title', 'Apply')

@section('content')
<div class="page-header">
    <h4>Application Form</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('applicant.apply') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">School</label>
                    <select name="school_id" class="form-control" required>
                        <option value="">Select School</option>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme</label>
                    <select name="programme_id" class="form-control" required>
                        <option value="">Select Programme</option>
                        @foreach($programmes as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Session</label>
                    <select name="session_id" class="form-control" required>
                        <option value="">Select Session</option>
                        @foreach($sessions as $session)
                        <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Submit Application</button>
        </form>
    </div>
</div>
@endsection