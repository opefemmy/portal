@extends('layouts.app')

@section('title', 'Department Timetable')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-calendar-alt me-2"></i>Department Timetable</h4>
</div>

@php
    $user = auth()->user();
    $departmentId = $user->department_id;
    $timetables = collect();
    if ($departmentId) {
        $timetables = \App\Models\Timetable::with(['courseAssignment.course.department', 'courseAssignment.lecturer', 'session'])
            ->whereHas('courseAssignment.course', function($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            })
            ->latest()
            ->get();
    }
@endphp

@if(!$departmentId)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-2"></i>
    You are not assigned to any department. Please contact the administrator.
</div>
@elseif($timetables->isEmpty())
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-calendar fa-4x text-muted mb-3"></i>
        <h5>No timetables yet</h5>
        <p class="text-muted">No timetables have been scheduled for your department.</p>
    </div>
</div>
@else
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Department Timetables</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Lecturer</th>
                        <th>Session</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Venue</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($timetables as $t)
                    <tr>
                        <td>
                            <strong>{{ $t->course->code ?? 'N/A' }}</strong><br>
                            <small class="text-muted">{{ $t->course->title ?? '' }}</small>
                        </td>
                        <td>{{ $t->lecturer->name ?? 'N/A' }}</td>
                        <td>{{ $t->session->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($t->day ?? 'N/A') }}</td>
                        <td>{{ $t->start_time ? $t->start_time->format('H:i') : '' }} - {{ $t->end_time ? $t->end_time->format('H:i') : '' }}</td>
                        <td>{{ $t->venue ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ ($t->status ?? 'pending') == 'approved' ? 'success' : (($t->status ?? '') == 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($t->status ?? 'pending') }}
                            </span>
                        </td>
                        <td>
                            @if(($t->status ?? 'pending') == 'pending')
                            <form method="POST" action="{{ route('hod.timetable.approve', $t) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this timetable?')">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('hod.timetable.reject', $t) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this timetable?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            @else
                            <span class="text-muted small">Already {{ $t->status }}</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection