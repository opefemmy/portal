@extends('layouts.app')

@section('title', 'Duty Roster')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Duty Roster</h3>
            <small class="text-muted">
                Week of {{ $weekStart->format('d M Y') }} &mdash; {{ $weekEnd->format('d M Y') }}
            </small>
        </div>
        <div class="btn-group">
            <a href="{{ route('hospital.roster.index', ['week_start' => $weekStart->copy()->subWeek()->toDateString()]) }}"
               class="btn btn-outline-secondary"><i class="fas fa-chevron-left"></i> Prev</a>
            <a href="{{ route('hospital.roster.index') }}" class="btn btn-outline-primary">This Week</a>
            <a href="{{ route('hospital.roster.index', ['week_start' => $weekStart->copy()->addWeek()->toDateString()]) }}"
               class="btn btn-outline-secondary">Next <i class="fas fa-chevron-right"></i></a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-primary text-white"><i class="fas fa-plus me-1"></i> Add Roster Entry</div>
        <div class="card-body">
            <form method="POST" action="{{ route('hospital.roster.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small">Staff</label>
                    <select name="staff_id" class="form-select" required>
                        <option value="">Select staff…</option>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}">{{ $s->full_name }} ({{ ucfirst($s->staff_type) }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Date</label>
                    <input type="date" name="duty_date" class="form-control" required value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">Start</label>
                    <input type="time" name="start_time" class="form-control" required value="08:00">
                </div>
                <div class="col-md-1">
                    <label class="form-label small">End</label>
                    <input type="time" name="end_time" class="form-control" required value="16:00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Shift</label>
                    <select name="shift" class="form-select" required>
                        <option value="morning">Morning</option>
                        <option value="evening">Evening</option>
                        <option value="night">Night</option>
                        <option value="on_call">On Call</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Location</label>
                    <input type="text" name="location" class="form-control" placeholder="Ward A / OPD / Lab">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save"></i></button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <strong>Roster ({{ $roster->count() }} entries)</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th><th>Staff</th><th>Type</th><th>Shift</th>
                        <th>Hours</th><th>Location</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roster as $r)
                        <tr>
                            <td>{{ $r->duty_date->format('D d M') }}</td>
                            <td>{{ $r->staff?->full_name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($r->staff?->staff_type ?? '—') }}</span></td>
                            <td><span class="badge bg-{{ $r->shift==='night'?'dark':($r->shift==='evening'?'warning':'success') }}">{{ ucfirst($r->shift) }}</span></td>
                            <td>{{ $r->start_time }} – {{ $r->end_time }}</td>
                            <td>{{ $r->location ?: '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('hospital.roster.destroy', $r->id) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this roster entry?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No roster entries for this week.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
