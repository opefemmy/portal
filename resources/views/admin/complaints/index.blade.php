@extends('layouts.app')

@section('title', 'Complaints Management')

@section('content')
<div class="page-header">
    <h4>Complaints Management</h4>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.complaints.index') }}" class="row g-3">
            <div class="col-md-4">
                <select name="status" class="form-select">
                    <option value="all">All Status</option>
                    <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Summary --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Pending</h6>
                <h3>{{ $complaints->where('status', 'pending')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <h6 class="text-muted">In Progress</h6>
                <h3>{{ $complaints->where('status', 'in_progress')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Resolved</h6>
                <h3>{{ $complaints->where('status', 'resolved')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Rejected</h6>
                <h3>{{ $complaints->where('status', 'rejected')->count() }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Category</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($complaints as $complaint)
                <tr>
                    <td>{{ $complaint->student->user->name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($complaint->category) }}</td>
                    <td>{{ $complaint->subject }}</td>
                    <td>
                        @if($complaint->status === 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($complaint->status === 'in_progress')
                            <span class="badge bg-info">In Progress</span>
                        @elseif($complaint->status === 'resolved')
                            <span class="badge bg-success">Resolved</span>
                        @else
                            <span class="badge bg-danger">Rejected</span>
                        @endif
                    </td>
                    <td>{{ $complaint->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('admin.complaints.show', $complaint) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">No complaints found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $complaints->links() }}
    </div>
</div>
@endsection