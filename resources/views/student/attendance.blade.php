@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="page-header"><h4><i class="fas fa-clipboard-check me-2"></i>Class Attendance</h4></div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Date</label>
        <input type="date" name="date" class="form-control form-control-sm" value="{{ $date ?? date('Y-m-d') }}">
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Date</th><th>Course</th><th>Status</th><th>Note</th></tr></thead>
        <tbody>
            @forelse($records ?? [] as $r)
                <tr>
                    <td>{{ optional($r->date)->format('M d, Y') ?? $r->created_at?->format('M d, Y') }}</td>
                    <td>{{ $r->course->code ?? '—' }} <small class="text-muted">{{ $r->course->title ?? '' }}</small></td>
                    <td><span class="badge bg-{{ $r->status === 'present' ? 'success' : ($r->status === 'absent' ? 'danger' : 'warning') }}">{{ ucfirst($r->status ?? 'present') }}</span></td>
                    <td>{{ $r->note ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center py-4 text-muted">No attendance records yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="mt-3">{{ ($records ?? null)?->appends(request()->query())->links() }}</div>
</div></div>
@endsection
