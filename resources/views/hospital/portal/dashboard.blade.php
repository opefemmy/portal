@extends('layouts.app')

@section('title', 'My Dashboard')

@section('content')
<style>
    .welcome-header {
        background: linear-gradient(135deg, #dc3545, #b21f3d);
        color: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .stat-icon {
        font-size: 2rem;
        opacity: 0.3;
    }
    .quick-link {
        transition: all 0.3s;
    }
    .quick-link:hover {
        transform: translateY(-5px);
    }
</style>

<div class="welcome-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h3><i class="fas fa-user me-2"></i>Welcome, {{ session('external_patient_name') }}</h3>
            <p class="mb-0">Patient Number: {{ session('external_patient_number') }}</p>
        </div>
        <form method="POST" action="{{ route('patient-portal.logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-light">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </button>
        </form>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-md-3">
        <a href="{{ route('patient-portal.dashboard') }}#history" class="quick-link text-decoration-none">
            <div class="card h-100 text-center p-4" style="background: linear-gradient(135deg, #dc3545, #b21f3d); color: white; border: none;">
                <i class="fas fa-file-medical-alt fa-3x mb-3"></i>
                <h5>Medical History</h5>
                <small>View all visits & records</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('patient-portal.payments') }}" class="quick-link text-decoration-none">
            <div class="card h-100 text-center p-4" style="background: #28a745; color: white; border: none;">
                <i class="fas fa-credit-card fa-3x mb-3"></i>
                <h5>Payments</h5>
                <small>View payment history</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('patient-portal.prescriptions') }}" class="quick-link text-decoration-none">
            <div class="card h-100 text-center p-4" style="background: #17a2b8; color: white; border: none;">
                <i class="fas fa-calendar-check fa-3x mb-3"></i>
                <h5>Prescriptions</h5>
                <small>View your prescriptions</small>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="{{ route('patient-portal.profile') }}" class="quick-link text-decoration-none">
            <div class="card h-100 text-center p-4" style="background: #6c757d; color: white; border: none;">
                <i class="fas fa-user-cog fa-3x mb-3"></i>
                <h5>My Profile</h5>
                <small>Update information</small>
            </div>
        </a>
    </div>
</div>

<!-- Pending Payments Alert -->
@if($pendingPayments->count() > 0)
<div class="alert alert-warning">
    <h5><i class="fas fa-exclamation-triangle me-2"></i>Pending Payments</h5>
    <p class="mb-0">You have {{ $pendingPayments->count() }} pending payment(s). Please pay to access services.</p>
    <a href="{{ route('patient-portal.payments') }}" class="btn btn-warning mt-2">View Pending Payments</a>
</div>
@endif

<!-- Upcoming Appointments -->
@if($upcomingAppointments->count() > 0)
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Upcoming Appointments</h5>
    </div>
    <div class="card-body">
        @foreach($upcomingAppointments as $appointment)
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
            <div>
                <strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y, h:i A') }}</strong>
                <p class="mb-0">{{ $appointment->purpose }}</p>
            </div>
            <span class="badge bg-info">{{ ucfirst($appointment->status) }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Recent Visits -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Visits</h5>
    </div>
    <div class="card-body">
        @forelse($recentVisits as $visit)
        <div class="border-bottom pb-2 mb-2">
            <div class="d-flex justify-content-between">
                <div>
                    <strong>{{ $visit->visit_number }}</strong> - {{ optional($visit->visit_date)->format('d M Y') ?? 'N/A' }}
                    <p class="mb-0 text-muted">{{ Str::limit($visit->chief_complaint ?? 'No complaint recorded', 50) }}</p>
                </div>
                <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : 'warning' }}">{{ ucfirst($visit->status) }}</span>
            </div>
        </div>
        @empty
        <p class="text-muted">No visits yet. Your first visit will appear here after registration.</p>
        @endforelse
    </div>
</div>
@endsection
