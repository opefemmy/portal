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
                    <th>Time</th>
                    <th>Doctor</th>
                    <th>Complaint</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $appointment)
                <tr>
                    <td>{{ $appointment->appointment_date->format('d M Y') }}</td>
                    <td>{{ $appointment->appointment_time }}</td>
                    <td>Dr. {{ $appointment->doctor->last_name ?? 'N/A' }}</td>
                    <td>{{ Str::limit($appointment->complaint, 50) }}</td>
                    <td>
                        @switch($appointment->status)
                            @case('scheduled')
                                <span class="badge bg-primary">Scheduled</span>
                                @break
                            @case('confirmed')
                                <span class="badge bg-info">Confirmed</span>
                                @break
                            @case('checked_in')
                                <span class="badge bg-warning">Checked In</span>
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
                    <td colspan="5" class="text-center">No appointments found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $appointments->links() }}
    </div>
</div>
@endsection