@extends('layouts.app')

@section('title', 'My Hostel')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Hostel</h4>
    <a href="{{ route('student.hostel.apply') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Apply for Hostel
    </a>
</div>

@if($allocation)
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Current Hostel Allocation</h5>
                <span class="badge bg-{{ $allocation->status == 'active' ? 'success' : 'warning' }}">
                    {{ ucfirst($allocation->status) }}
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Hostel:</strong> {{ $allocation->hostel->name }}</p>
                        <p><strong>Room:</strong> {{ $allocation->room->room_number }}</p>
                        <p><strong>Floor:</strong> {{ $allocation->room->floor }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Session:</strong> {{ $allocation->session->name ?? 'N/A' }}</p>
                        <p><strong>Check-in Date:</strong> {{ $allocation->check_in_date }}</p>
                        @if($allocation->check_out_date)
                        <p><strong>Check-out Date:</strong> {{ $allocation->check_out_date }}</p>
                        @endif
                    </div>
                </div>

                @if($allocation->status == 'active')
                <div class="mt-3">
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changeRequestModal">
                        <i class="fas fa-exchange-alt me-2"></i>Request Room Change
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Change Request Modal -->
<div class="modal fade" id="changeRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('student.hostel.request-change') }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Room Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-bed fa-4x text-muted mb-3"></i>
        <h5>No Hostel Allocation</h5>
        <p class="text-muted">You don't have a hostel allocation yet.</p>
        <a href="{{ route('student.hostel.apply') }}" class="btn btn-primary">
            Apply for Hostel
        </a>
    </div>
</div>
@endif
@endsection