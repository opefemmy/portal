@extends('layouts.app')

@section('title', 'Exam Timetable')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-clipboard-list me-2"></i>Exam Timetable</h4>
    <a href="{{ route('admin.exam-timetable.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Exam Slot
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="GET" action="{{ route('admin.exam-timetable.index') }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Session</label>
        <select name="session_id" class="form-select form-select-sm">
            <option value="">All sessions</option>
            @foreach($sessions as $s)
                <option value="{{ $s->id }}" {{ request('session_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small mb-1">Semester</label>
        <select name="semester" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="First" {{ request('semester') == 'First' ? 'selected' : '' }}>First</option>
            <option value="Second" {{ request('semester') == 'Second' ? 'selected' : '' }}>Second</option>
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead class="table-light">
                    <tr>
                        <th>Course</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timetables as $t)
                        <tr>
                            <td><strong>{{ $t->course->code ?? '' }}</strong><br><small class="text-muted">{{ $t->course->title ?? '' }}</small></td>
                            <td>{{ $t->session->name ?? '—' }}</td>
                            <td>{{ $t->semester }}</td>
                            <td>{{ optional($t->exam_date)->format('M d, Y') ?? $t->exam_date }}</td>
                            <td>{{ \Carbon\Carbon::parse($t->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($t->end_time)->format('H:i') }}</td>
                            <td>{{ $t->venue ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.exam-timetable.edit', $t->id) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('admin.exam-timetable.destroy', $t->id) }}" class="d-inline" onsubmit="return confirm('Delete this exam slot?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No exam slots scheduled yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
