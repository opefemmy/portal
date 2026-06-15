@extends('layouts.app')

@section('title', 'Edit Timetable')

@section('content')
<div class="page-header">
    <h4>Edit Timetable</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.timetable.update', $timetable) }}">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ $timetable->course_id == $course->id ? 'selected' : '' }}>{{ $course->code }} - {{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session</label>
                        <select class="form-select" id="session_id" name="session_id" required>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" {{ $timetable->session_id == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="First" {{ $timetable->semester == 'First' ? 'selected' : '' }}>First</option>
                            <option value="Second" {{ $timetable->semester == 'Second' ? 'selected' : '' }}>Second</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" value="{{ $timetable->exam_date->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="venue" class="form-label">Venue</label>
                        <input type="text" class="form-control" id="venue" name="venue" value="{{ $timetable->venue }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" value="{{ $timetable->start_time->format('H:i') }}" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" value="{{ $timetable->end_time->format('H:i') }}" required>
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ $timetable->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update</button>
                <a href="{{ route('admin.timetable.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection