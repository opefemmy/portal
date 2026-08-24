@extends('layouts.app')

@section('title', 'My Attendance')

@section('content')
<div class="page-header"><h4><i class="fas fa-user-check me-2"></i>My Attendance Summary</h4></div>

@if(isset($summary))
<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3 bg-primary text-white"><small>Total Classes</small><h3>{{ $summary['total'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-success text-white"><small>Present</small><h3>{{ $summary['present'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-warning text-dark"><small>Late</small><h3>{{ $summary['late'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-danger text-white"><small>Absent</small><h3>{{ $summary['absent'] ?? 0 }}</h3></div></div>
</div>
@php
    $total = $summary['total'] ?? 0;
    $present = $summary['present'] ?? 0;
    $pct = $total > 0 ? round(($present / $total) * 100, 1) : 0;
@endphp
<div class="card mb-3">
    <div class="card-body text-center">
        <h2><span class="badge bg-{{ $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}">{{ $pct }}% Attendance</span></h2>
        <p class="text-muted mb-0">Eligibility for exams requires ≥ 75%</p>
    </div>
</div>
@endif

@if(($records ?? collect())->count())
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Date</th><th>Course</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($records as $r)
                <tr>
                    <td>{{ optional($r->date)->format('M d, Y') ?? $r->created_at?->format('M d, Y') }}</td>
                    <td>{{ $r->course->code ?? '—' }}</td>
                    <td><span class="badge bg-{{ $r->status === 'present' ? 'success' : ($r->status === 'absent' ? 'danger' : 'warning') }}">{{ ucfirst($r->status ?? 'present') }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table></div>
</div></div>
@else
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No attendance records yet.</div>
@endif
@endsection
