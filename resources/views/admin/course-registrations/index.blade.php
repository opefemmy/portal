@extends('layouts.app')

@section('title', 'Course Registration Report')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Course Registration Report</h4>
    <div>
        <a href="{{ route('admin.course-registrations.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-export me-2"></i>Export CSV
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="session_id" class="form-label">Session</label>
                <select class="form-select" id="session_id" name="session_id">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>
                            {{ $session->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="semester" class="form-label">Semester</label>
                <select class="form-select" id="semester" name="semester">
                    <option value="">All Semesters</option>
                    <option value="First" {{ request('semester') == 'First' ? 'selected' : '' }}>First</option>
                    <option value="Second" {{ request('semester') == 'Second' ? 'selected' : '' }}>Second</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="registered" {{ request('status') == 'registered' ? 'selected' : '' }}>Registered</option>
                    <option value="unsubmitted" {{ request('status') == 'unsubmitted' ? 'selected' : '' }}>Unsubmitted</option>
                    <option value="dropped" {{ request('status') == 'dropped' ? 'selected' : '' }}>Dropped</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
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
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Units</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($registrations as $reg)
                    <tr>
                        <td>{{ $reg->student->matric_number ?? 'N/A' }}</td>
                        <td>{{ $reg->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $reg->course->code ?? 'N/A' }}</td>
                        <td>{{ $reg->course->title ?? 'N/A' }}</td>
                        <td>{{ $reg->course->units ?? 0 }}</td>
                        <td>{{ $reg->session->name ?? 'N/A' }}</td>
                        <td>{{ $reg->semester }}</td>
                        <td>
                            <span class="badge bg-{{ $reg->status === 'registered' ? 'success' : ($reg->status === 'unsubmitted' ? 'warning' : 'danger') }}">
                                {{ ucfirst($reg->status) }}
                            </span>
                        </td>
                        <td>
                            @if($reg->status === 'registered')
                            <form method="POST" action="{{ route('admin.course-registrations.unsubmit', $reg) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Unsubmit this course" onclick="return confirm('Unsubmit this course registration?')">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </form>
                            @elseif($reg->status === 'unsubmitted')
                            <form method="POST" action="{{ route('admin.course-registrations.resubmit', $reg) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Resubmit this course">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">No course registrations found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection