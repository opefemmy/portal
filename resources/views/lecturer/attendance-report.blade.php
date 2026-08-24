@extends('layouts.app')

@section('title', 'Attendance Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-chart-bar me-2"></i>Attendance Report</h4></div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">From</label>
        <input type="date" name="from" class="form-control form-control-sm" value="{{ $from ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">To</label>
        <input type="date" name="to" class="form-control form-control-sm" value="{{ $to ?? '' }}">
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
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Generate</button></div>
</form>

@if(isset($summary))
<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3 bg-primary text-white"><small>Total</small><h3>{{ $summary['total'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-success text-white"><small>Present</small><h3>{{ $summary['present'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-warning text-dark"><small>Late</small><h3>{{ $summary['late'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-danger text-white"><small>Absent</small><h3>{{ $summary['absent'] ?? 0 }}</h3></div></div>
</div>
@endif

@if(($rows ?? collect())->count())
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Student</th><th>Course</th><th class="text-end">Present</th><th class="text-end">Late</th><th class="text-end">Absent</th><th class="text-end">% Attendance</th></tr></thead>
        <tbody>
            @foreach($rows as $row)
                @php $pct = $row->total > 0 ? round(($row->present / $row->total) * 100, 1) : 0; @endphp
                <tr>
                    <td>{{ $row->student_name ?? '—' }}</td>
                    <td>{{ $row->course_code ?? '—' }}</td>
                    <td class="text-end">{{ $row->present ?? 0 }}</td>
                    <td class="text-end">{{ $row->late ?? 0 }}</td>
                    <td class="text-end">{{ $row->absent ?? 0 }}</td>
                    <td class="text-end"><span class="badge bg-{{ $pct >= 75 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}">{{ $pct }}%</span></td>
                </tr>
            @endforeach
        </tbody>
    </table></div>
</div></div>
@endif
@endsection
