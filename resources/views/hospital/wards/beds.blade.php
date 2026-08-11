@extends('layouts.app')

@section('title', $ward->name . ' — Beds')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-bed me-2"></i>{{ $ward->name }} — Beds</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.wards.index') }}">Wards</a></li>
                <li class="breadcrumb-item active">{{ $ward->name }}</li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    @foreach($beds as $bed)
        @php
            $admission = $bed->admissions->first();
            $occupied  = $bed->status === 'occupied' && $admission;
        @endphp
        <div class="col-md-3 mb-3">
            <div class="card border-{{ $occupied ? 'danger' : 'success' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <strong>Bed {{ $bed->bed_number }}</strong>
                        <span class="badge bg-{{ $occupied ? 'danger' : 'success' }}">{{ ucfirst($bed->status) }}</span>
                    </div>
                    @if($occupied)
                        <hr>
                        <small class="text-muted">Patient</small><br>
                        <strong>{{ optional($admission->patient)->full_name }}</strong>
                        <br><small>Dr. {{ optional($admission->doctor)->last_name ?? 'TBA' }}</small>
                        <br><small>Admitted {{ optional($admission->admission_date)->format('d M Y') }}</small>
                        <form method="POST" action="{{ route('hospital.wards.beds.discharge', $bed) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                                    onclick="return confirm('Discharge this patient?')">
                                <i class="fas fa-sign-out-alt"></i> Discharge
                            </button>
                        </form>
                    @else
                        <button class="btn btn-sm btn-outline-success w-100 mt-2"
                                data-bs-toggle="modal" data-bs-target="#assign{{ $bed->id }}">
                            <i class="fas fa-user-plus"></i> Assign
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if(!$occupied)
        <div class="modal fade" id="assign{{ $bed->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('hospital.wards.beds.assign', $ward) }}">
                    @csrf
                    <input type="hidden" name="bed_id" value="{{ $bed->id }}">
                    <div class="modal-content">
                        <div class="modal-header"><h5>Assign Bed {{ $bed->bed_number }}</h5></div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Patient</label>
                                <select name="patient_id" class="form-select" required>
                                    <option value="">Choose...</option>
                                    @foreach($availablePatients as $patient)
                                        <option value="{{ $patient->id }}">{{ $patient->full_name }} ({{ $patient->patient_number }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Doctor</label>
                                <select name="doctor_id" class="form-select" required>
                                    <option value="">Choose...</option>
                                    @foreach($doctors as $doctor)
                                        <option value="{{ $doctor->id }}">Dr. {{ $doctor->full_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="2" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Diagnosis</label>
                                <textarea name="diagnosis" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Daily rate (override)</label>
                                <input type="number" name="daily_rate" step="0.01" min="0" class="form-control"
                                       value="{{ $ward->daily_rate }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Assign</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif
    @endforeach
</div>
@endsection