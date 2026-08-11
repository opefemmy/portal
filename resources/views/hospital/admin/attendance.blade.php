@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-calendar-check me-2"></i>Attendance — last 7 days</h4>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hospital.admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item active">Attendance</li>
    </ul>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title"><i class="fas fa-chart-pie me-2"></i>Summary</h5></div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Day</th><th class="text-end">Scheduled</th><th class="text-end">Filled</th><th>%</th></tr></thead>
            <tbody>
                @forelse($summary as $row)
                    @php $pct = $row['total'] > 0 ? round($row['filled'] / $row['total'] * 100) : 0; @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row['day'])->format('D d M') }}</td>
                        <td class="text-end">{{ $row['total'] }}</td>
                        <td class="text-end">{{ $row['filled'] }}</td>
                        <td>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $pct >= 80 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}" style="width: {{ $pct }}%"></div>
                            </div>
                            <small>{{ $pct }}%</small>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">No roster entries in the last 7 days.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@foreach($rows as $day => $entries)
    <div class="card mb-2">
        <div class="card-header"><strong>{{ \Carbon\Carbon::parse($day)->format('l, d M Y') }}</strong> — {{ $entries->count() }} shift(s)</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Staff</th><th>Shift</th><th>Location</th><th>Hours</th></tr></thead>
                <tbody>
                    @foreach($entries as $e)
                        <tr>
                            <td>{{ optional($e->staff)->full_name ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($e->shift ?? '—') }}</span></td>
                            <td>{{ $e->location ?? '—' }}</td>
                            <td>{{ \Carbon\Carbon::parse($e->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($e->end_time)->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection