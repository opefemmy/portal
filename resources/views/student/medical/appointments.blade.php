@extends('layouts.app')

@section('title', 'My Appointments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Appointments</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Book New Appointment
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Appointment No.</th>
                    <th>Symptoms</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $appointment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}</td>
                    <td>APT-{{ str_pad($appointment->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ Str::limit($appointment->complaint, 50) }}</td>
                    <td>
                        @switch($appointment->status)
                            @case('scheduled')
                                <span class="badge bg-primary">Scheduled</span>
                                @break
                            @case('completed')
                                <span class="badge bg-success">Completed</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ $appointment->status }}</span>
                        @endswitch
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No appointments found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $appointments->links() }}
    </div>
</div>
@endsection
