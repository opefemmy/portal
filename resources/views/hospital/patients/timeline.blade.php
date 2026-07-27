@extends('layouts.app')

@section('title', 'Patient Timeline')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Patient Timeline: {{ $patient->first_name }} {{ $patient->last_name }}
                        <span class="badge bg-primary">{{ $patient->patient_number }}</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5>Patient Info</h5>
                                    <p><strong>Name:</strong> {{ $patient->first_name }} {{ $patient->last_name }}</p>
                                    <p><strong>Number:</strong> {{ $patient->patient_number }}</p>
                                    <p><strong>Type:</strong> {{ ucfirst($patient->patient_type) }}</p>
                                    <p><strong>Gender:</strong> {{ ucfirst($patient->gender) }}</p>
                                    <p><strong>Phone:</strong> {{ $patient->phone }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4>Medical History Timeline</h4>

                            @if($patient->appointments->count() > 0)
                            <div class="timeline-item mb-3">
                                <h6><i class="fas fa-calendar"></i> Appointments</h6>
                                <ul class="list-group">
                                    @foreach($patient->appointments as $appointment)
                                    <li class="list-group-item">
                                        <strong>{{ $appointment->appointment_date->format('d M Y') }}</strong> -
                                        Status: {{ ucfirst($appointment->status) }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($patient->prescriptions->count() > 0)
                            <div class="timeline-item mb-3">
                                <h6><i class="fas fa-prescription"></i> Prescriptions</h6>
                                <ul class="list-group">
                                    @foreach($patient->prescriptions as $prescription)
                                    <li class="list-group-item">
                                        <strong>{{ $prescription->created_at->format('d M Y') }}</strong> -
                                        Status: {{ ucfirst($prescription->status) }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($patient->labRequests->count() > 0)
                            <div class="timeline-item mb-3">
                                <h6><i class="fas fa-flask"></i> Lab Requests</h6>
                                <ul class="list-group">
                                    @foreach($patient->labRequests as $lab)
                                    <li class="list-group-item">
                                        <strong>{{ $lab->created_at->format('d M Y') }}</strong> -
                                        {{ $lab->test_type }} - Status: {{ ucfirst($lab->status) }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($patient->admissions->count() > 0)
                            <div class="timeline-item mb-3">
                                <h6><i class="fas fa-procedures"></i> Admissions</h6>
                                <ul class="list-group">
                                    @foreach($patient->admissions as $admission)
                                    <li class="list-group-item">
                                        <strong>{{ $admission->admission_date->format('d M Y') }}</strong> -
                                        Status: {{ ucfirst($admission->status) }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            @if($patient->appointments->count() == 0 && $patient->prescriptions->count() == 0 && $patient->labRequests->count() == 0 && $patient->admissions->count() == 0)
                            <div class="alert alert-info">
                                No medical history found for this patient.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('hospital.patients.show', $patient->id) }}" class="btn btn-secondary">Back to Patient</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
