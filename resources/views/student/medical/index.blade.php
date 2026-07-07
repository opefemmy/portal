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
                        <a href="{{ route('student.medical.admissions') }}" class="btn btn-outline-primary">
                            <i class="fas fa-procedures me-2"></i>Admissions
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
                    <p><strong>Patient No:</strong> {{ $patient->patient_number }}</p>
                    <p><strong>Name:</strong> {{ $patient->full_name }}</p>
                    <p><strong>Gender:</strong> {{ ucfirst($patient->gender) }}</p>
                    <p><strong>Age:</strong> {{ $patient->age }} years</p>
                    <p><strong>Blood Group:</strong> {{ $patient->blood_group ?? 'Not Set' }}</p>
                    <p><strong>Genotype:</strong> {{ $patient->genotype ?? 'Not Set' }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Recent Appointments -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Recent Appointments</h5>
                </div>
                <div class="card-body">
                    @forelse($appointments as $appointment)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>{{ $appointment->appointment_date->format('d M Y') }}</strong>
                            <br><small>Dr. {{ $appointment->doctor->last_name ?? 'N/A' }}</small>
                        </div>
                        <span class="badge bg-{{ $appointment->status === 'completed' ? 'success' : 'warning' }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted">No appointments</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Prescriptions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Prescriptions</h5>
                </div>
                <div class="card-body">
                    @forelse($prescriptions as $prescription)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <strong>{{ $prescription->created_at->format('d M Y') }}</strong>
                            <br><small>{{ $prescription->items->count() }} item(s)</small>
                        </div>
                        <span class="badge bg-{{ $prescription->status === 'dispensed' ? 'success' : 'warning' }}">
                            {{ ucfirst($prescription->status) }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted">No prescriptions</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection