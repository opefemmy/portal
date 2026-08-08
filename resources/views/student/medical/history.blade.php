@extends('layouts.app')

@section('title', 'Medical History')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Medical History</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Medical Portal
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
                    <th>Notes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $appointment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}</td>
                    <td>APT-{{ str_pad($appointment->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ Str::limit($appointment->complaint, 30) }}</td>
                    <td>{{ Str::limit($appointment->notes, 30) }}</td>
                    <td>
                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No medical history found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $appointments->links() }}
    </div>
</div>
@endsection
