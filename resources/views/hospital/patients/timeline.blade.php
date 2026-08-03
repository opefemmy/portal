@extends('layouts.app')

@section('title', 'Patient Timeline')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-stream me-2"></i>
                        Patient Timeline: {{ $patient->first_name }} {{ $patient->last_name }}
                        <span class="badge bg-light text-primary ms-2">{{ $patient->patient_number }}</span>
                    </h3>
                    <a href="{{ route('hospital.patients.show', $patient->id) }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5>Patient Info</h5>
                                    <p class="mb-1"><strong>Name:</strong> {{ $patient->full_name }}</p>
                                    <p class="mb-1"><strong>Number:</strong> {{ $patient->patient_number }}</p>
                                    <p class="mb-1"><strong>Type:</strong> {{ ucfirst($patient->patient_type) }}</p>
                                    <p class="mb-1"><strong>Gender:</strong> {{ ucfirst($patient->gender) }}</p>
                                    <p class="mb-1"><strong>Phone:</strong> {{ $patient->phone }}</p>
                                    <p class="mb-1"><strong>Age:</strong> {{ optional($patient->date_of_birth)->age ?? '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-3"><i class="fas fa-history me-1"></i> Unified Medical Timeline</h4>

                            @if(empty($events) || count($events) === 0)
                                <div class="alert alert-info">No medical history found for this patient.</div>
                            @else
                                <div class="timeline">
                                    @foreach($events as $e)
                                        <div class="timeline-item mb-3 ps-4 border-start border-{{ $e['color'] }} border-3 position-relative">
                                            <span class="position-absolute top-0 start-0 translate-middle bg-{{ $e['color'] }} text-white rounded-circle d-flex align-items-center justify-content-center"
                                                  style="width:32px;height:32px;margin-left:-16px;">
                                                <i class="{{ $e['icon'] }}"></i>
                                            </span>
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong>{{ $e['label'] }}</strong>
                                                    <span class="text-muted ms-2">by {{ $e['actor'] }}</span>
                                                </div>
                                                <small class="text-muted">{{ optional($e['when'])->format('d M Y H:i') }}</small>
                                            </div>
                                            <div>{{ $e['summary'] }}</div>
                                            @if(!empty($e['detail_url']))
                                                <a href="{{ $e['detail_url'] }}" class="btn btn-sm btn-link p-0">View →</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
