@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-calendar-check me-2"></i>My Appointments</h3>
    <p class="text-muted">Patient: <strong>{{ $patient->full_name }}</strong></p>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Status</th><th>Notes</th></tr>
                </thead>
                <tbody>
                    @forelse($appointments ?? collect() as $a)
                        <tr>
                            <td>{{ optional($a->appointment_date)->format('d M Y H:i') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $a->status === 'completed' ? 'success' : ($a->status === 'cancelled' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($a->status ?? 'scheduled') }}
                                </span>
                            </td>
                            <td>{{ $a->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">No appointments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(isset($appointments) && method_exists($appointments, 'links'))
                <div class="p-3">{{ $appointments->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection