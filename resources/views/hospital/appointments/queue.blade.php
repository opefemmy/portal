@extends('layouts.app')

@section('title', 'Today\'s Queue')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-list-ol me-2"></i>Today's Queue</h3>
        <a href="{{ route('hospital.appointments.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> All Appointments
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appointments ?? collect() as $i => $a)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $a->patient?->full_name ?? '—' }}<br><small class="text-muted">{{ $a->patient?->patient_number ?? '' }}</small></td>
                            <td>Dr. {{ $a->doctor?->full_name ?? '—' }}</td>
                            <td>{{ optional($a->appointment_date)->format('h:i A') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $a->status === 'completed' ? 'success' : ($a->status === 'in_progress' ? 'primary' : 'warning') }}">
                                    {{ ucfirst(str_replace('_', ' ', $a->status ?? 'scheduled')) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('hospital.appointments.show', $a->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No appointments in queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection