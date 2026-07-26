@extends('layouts.app')

@section('title', 'Lab Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Lab Results</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Medical Portal
    </a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted">Your lab results will appear here after tests.</p>

        <table class="table datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Appointment No.</th>
                    <th>Notes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $appointment)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}</td>
                    <td>{{ $appointment->appointment_number }}</td>
                    <td>{{ Str::limit($appointment->notes, 50) }}</td>
                    <td>
                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center">No lab results found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $appointments->links() }}
    </div>
</div>
@endsection
