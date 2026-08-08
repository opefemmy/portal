@extends('layouts.app')

@section('title', 'Medical Portal')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4"><i class="fas fa-hospital me-2"></i>Medical Portal</h4>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="{{ route('student.medical.book') }}" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book Appointment
                        </a>
                        <a href="{{ route('student.medical.appointments') }}" class="btn btn-outline-primary">
                            <i class="fas fa-calendar me-2"></i>My Appointments
                        </a>
                        <a href="{{ route('student.medical.history') }}" class="btn btn-outline-primary">
                            <i class="fas fa-file-medical me-2"></i>Medical History
                        </a>
                        <a href="{{ route('student.medical.prescriptions') }}" class="btn btn-outline-primary">
                            <i class="fas fa-prescription me-2"></i>Prescriptions
                        </a>
                        <a href="{{ route('student.medical.lab-results') }}" class="btn btn-outline-primary">
                            <i class="fas fa-vial me-2"></i>Lab Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Patient Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $patient->first_name }} {{ $patient->last_name }}</p>
                    <p><strong>Gender:</strong> {{ ucfirst($patient->gender ?? 'Not Set') }}</p>
                    <p><strong>Phone:</strong> {{ $patient->phone ?? 'Not Set' }}</p>
                    <p><strong>Blood Type:</strong> {{ $patient->blood_group ?? 'Not Set' }}</p>
                    <p><strong>Allergies:</strong> {{ $patient->allergies ?? 'None' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Recent Appointments -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Appointments</h5>
                </div>
                <div class="card-body">
                    @forelse($appointments as $appointment)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y') }}</strong>
                            <br><small>{{ $appointment->complaint ?? 'No reason recorded' }}</small>
                        </div>
                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted">No appointments yet. <a href="{{ route('student.medical.book') }}">Book your first appointment</a></p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
