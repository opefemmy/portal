@extends('layouts.app')

@section('title', 'Lecture Timetable')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Lecture Timetable</h4>
    <a href="{{ route('admin.timetable.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Timetable
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select class="form-select" name="session_id">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="semester">
                    <option value="">All Semesters</option>
                    <option value="First" {{ request('semester') == 'First' ? 'selected' : '' }}>First</option>
                    <option value="Second" {{ request('semester') == 'Second' ? 'selected' : '' }}>Second</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timetables as $exam)
                    <tr>
                        <td>{{ $exam->course->code ?? 'N/A' }}</td>
                        <td>{{ $exam->course->title ?? 'N/A' }}</td>
                        <td>{{ $exam->session->name ?? 'N/A' }}</td>
                        <td>{{ $exam->semester }}</td>
                        <td>{{ $exam->exam_date->format('d M Y') }}</td>
                        <td>{{ $exam->start_time->format('h:i A') }} - {{ $exam->end_time->format('h:i A') }}</td>
                        <td>{{ $exam->venue ?? 'TBA' }}</td>
                        <td>
                            <a href="{{ route('admin.timetable.edit', $exam) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.timetable.destroy', $exam) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete" onclick="return confirm('Delete this timetable?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">No timetables found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection