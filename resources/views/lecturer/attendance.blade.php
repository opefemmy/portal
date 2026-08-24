@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="page-header"><h4><i class="fas fa-clipboard-check me-2"></i>Attendance</h4></div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Date</label>
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date ?? date('Y-m-d') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label small mb-1">Course</label>
        <select name="course_id" class="form-select form-select-sm">
            <option value="">All my courses</option>
            @foreach($courses ?? [] as $c)
                <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->code }} — {{ $c->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Date</th><th>Course</th><th>Student</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($records ?? [] as $r)
                <tr>
                    <td>{{ optional($r->date)->format('M d, Y') ?? $r->created_at?->format('M d, Y') }}</td>
                    <td>{{ $r->course->code ?? '—' }}</td>
                    <td>{{ $r->student->user->name ?? $r->student_name ?? '—' }}</td>
                    <td><span class="badge bg-{{ $r->status === 'present' ? 'success' : ($r->status === 'absent' ? 'danger' : 'warning') }}">{{ ucfirst($r->status ?? 'present') }}</span></td>
                    <td>
                        <form method="POST" action="{{ route('lecturer.attendance.update', $r) }}" class="d-inline">
                            @csrf @method('PUT')
                            <select name="status" class="form-select form-select-sm d-inline-block" style="width:auto">
                                @foreach(['present','absent','late'] as $s)
                                    <option value="{{ $s }}" {{ ($r->status ?? '') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-save"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4 text-muted">No attendance records for this filter.</td></tr>
            @endforelse
        </tbody>
    </table></div>
</div></div>
@endsection
