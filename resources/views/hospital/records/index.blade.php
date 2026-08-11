@extends('layouts.app')

@section('title', 'Medical Records')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-folder-open me-2"></i>Medical Records</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item active">Records</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto d-flex gap-2">
            <a href="{{ route('hospital.records.search') }}" class="btn btn-outline-info"><i class="fas fa-search me-1"></i> Search</a>
            <a href="{{ route('hospital.records.audit') }}" class="btn btn-outline-secondary"><i class="fas fa-history me-1"></i> Audit Log</a>
            <a href="{{ route('hospital.records.requests') }}" class="btn btn-outline-warning position-relative">
                <i class="fas fa-inbox me-1"></i> Requests
                @if($pendingRequests > 0)
                    <span class="badge bg-danger">{{ $pendingRequests }}</span>
                @endif
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title"><i class="fas fa-users me-2"></i>All Patients</h5>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Number</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th class="text-end">Records</th>
                    <th class="text-end">Rx</th>
                    <th class="text-end">Lab</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $p)
                    <tr>
                        <td><strong>{{ $p->full_name }}</strong></td>
                        <td><code>{{ $p->patient_number }}</code></td>
                        <td>{{ ucfirst($p->gender ?? '—') }}</td>
                        <td>{{ $p->phone ?? '—' }}</td>
                        <td class="text-end">{{ $p->medical_records_count }}</td>
                        <td class="text-end">{{ $p->prescriptions_count }}</td>
                        <td class="text-end">{{ $p->lab_requests_count }}</td>
                        <td>
                            @if($p->archived_at)
                                <span class="badge bg-secondary">Archived</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('hospital.records.show', $p) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No patient records.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $patients->links() }}
    </div>
</div>
@endsection