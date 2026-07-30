@extends('layouts.app')

@section('title', 'My Prescriptions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">My Prescriptions</h2>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ !request('status') ? 'active' : '' }}" href="{{ route('patient-portal.prescriptions') }}">
                        All Prescriptions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') === 'pending' ? 'active' : '' }}" href="{{ route('patient-portal.prescriptions', ['status' => 'pending']) }}">
                        Pending Payment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') === 'paid' ? 'active' : '' }}" href="{{ route('patient-portal.prescriptions', ['status' => 'paid']) }}">
                        Paid/Ready
                    </a>
                </li>
            </ul>
        </div>
    </div>

    @forelse($prescriptions as $prescription)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Prescription #{{ $prescription->id }}</strong>
                <span class="badge bg-{{ $prescription->payment_status === 'paid' ? 'success' : 'warning' }} ms-2">
                    {{ ucfirst($prescription->payment_status ?? 'pending') }}
                </span>
            </div>
            <small>{{ $prescription->created_at->format('d M Y, h:i A') }}</small>
        </div>
        <div class="card-body">
            <!-- Visit Info -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6>Visit Details</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Doctor:</th>
                            <td>{{ $prescription->visit->doctor->name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Complaint:</th>
                            <td>{{ $prescription->visit->complaint ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Doctor's Notes</h6>
                    <p>{{ $prescription->notes ?? 'No additional notes' }}</p>
                </div>
            </div>

            <!-- Prescription Items -->
            <h6>Prescribed Medications</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Instructions</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prescription->items as $item)
                        <tr>
                            <td>{{ $item->medication_name }}</td>
                            <td>{{ $item->dosage ?? '-' }}</td>
                            <td>{{ $item->frequency ?? '-' }}</td>
                            <td>{{ $item->duration ?? '-' }}</td>
                            <td>{{ $item->instructions ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No medications prescribed</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Payment & Action -->
            <div class="mt-3">
                @if($prescription->payment_status === 'paid')
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Prescription Paid!</strong> Please proceed to the pharmacy to collect your medications.
                </div>
                <a href="{{ route('patient-portal.prescription', $prescription->id) }}" class="btn btn-primary">
                    <i class="fas fa-print me-2"></i>View Prescription Slip
                </a>
                @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Payment Required</strong> Please pay for your prescription to proceed to the pharmacy.
                </div>
                <form method="POST" action="{{ route('patient-portal.prescription.pay', $prescription->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-credit-card me-2"></i>Pay for Prescription
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="alert alert-info">
        <p class="mb-0">No prescriptions found. Prescriptions will appear here after your doctor visit.</p>
    </div>
    @endforelse
</div>
@endsection
