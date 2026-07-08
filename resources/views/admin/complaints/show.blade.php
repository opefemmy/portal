@extends('layouts.app')

@section('title', 'Complaint Details')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Complaint Details</h4>
    <a href="{{ route('admin.complaints.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ $complaint->subject }}</h5>
            </div>
            <div class="card-body">
                <p><strong>Category:</strong> {{ ucfirst($complaint->category) }}</p>
                <p><strong>Student:</strong> {{ $complaint->student->user->name ?? 'N/A' }}</p>
                <p><strong>Matric Number:</strong> {{ $complaint->student->matric_number ?? 'N/A' }}</p>
                <p><strong>Submitted:</strong> {{ $complaint->created_at->format('d M Y, h:i A') }}</p>
                <hr>
                <h6>Message:</h6>
                <p>{{ $complaint->message }}</p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Update Status</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.complaints.update', $complaint) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ $complaint->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ $complaint->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $complaint->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="rejected" {{ $complaint->status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Response</label>
                        <textarea name="admin_response" class="form-control" rows="4">{{ $complaint->admin_response }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Status</h5>
            </div>
            <div class="card-body text-center">
                @if($complaint->status === 'pending')
                    <span class="badge bg-warning fs-5">Pending</span>
                @elseif($complaint->status === 'in_progress')
                    <span class="badge bg-info fs-5">In Progress</span>
                @elseif($complaint->status === 'resolved')
                    <span class="badge bg-success fs-5">Resolved</span>
                @else
                    <span class="badge bg-danger fs-5">Rejected</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection