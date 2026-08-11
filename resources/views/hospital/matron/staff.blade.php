@extends('layouts.app')

@section('title', 'Staff Load')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-user-clock me-2"></i>Staff Load</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.matron.dashboard') }}">Matron</a></li>
                <li class="breadcrumb-item active">Staff</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto">
            <span class="badge bg-info">Week of {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M') }}</span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-calendar-week me-2"></i>Roster</h5>
            </div>
            <div class="card-body" style="max-height: 600px; overflow-y:auto;">
                @forelse($roster as $entry)
                    <div class="border-bottom py-2 d-flex justify-content-between">
                        <div>
                            <strong>{{ optional($entry->staff)->full_name ?? '—' }}</strong>
                            <br><small class="text-muted">{{ $entry->shift ?? '—' }} @ {{ $entry->location ?? '—' }}</small>
                        </div>
                        <div class="text-end">
                            <small>{{ \Carbon\Carbon::parse($entry->duty_date)->format('D d M') }}</small>
                            <br><small>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center">No roster entries for this week.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-users me-2"></i>Nurses — Weekly Appointments</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Nurse</th><th>Type</th><th class="text-end">Appointments this week</th></tr></thead>
                    <tbody>
                        @forelse($staffLoad as $nurse)
                        <tr>
                            <td>{{ $nurse->full_name }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($nurse->staff_type) }}</span></td>
                            <td class="text-end">{{ $nurse->appointments_count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">No nursing staff on file.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection