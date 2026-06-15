@extends('layouts.app')

@section('title', 'Create Course')

@section('content')
<div class="page-header">
    <h4>Create Course</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.courses.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="code" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Course Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Units</label>
                    <input type="number" name="units" class="form-control" min="1" max="10" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-control" required>
                        <option value="first">First</option>
                        <option value="second">Second</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">School</label>
                    <select name="school_id" class="form-control" required>
                        @foreach($schools as $school)
                        <option value="{{ $school->id }}">{{ $school->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-control" required>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Programme</label>
                    <select name="programme_id" class="form-control" required>
                        @foreach($programmes as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Level</label>
                    <input type="number" name="level" class="form-control" min="1" max="6" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Create Course</button>
            <a href="{{ route('admin.courses.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection