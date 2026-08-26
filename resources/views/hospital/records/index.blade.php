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

<div class="card mb-3">
    <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-clipboard-list me-2"></i>Awaiting Records Certification
            @if(($pendingCertifications ?? collect())->count() > 0)
                <span class="badge bg-warning text-dark ms-2">{{ $pendingCertifications->count() }}</span>
            @endif
        </h5>
        <small class="text-muted">Today's appointments still waiting for the records officer to certify the chart.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Number</th>
                    <th>Doctor</th>
                    <th>Status</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingCertifications ?? collect() as $a)
                    <tr>
                        <td>{{ $a->appointment_time ?? '—' }}</td>
                        <td>{{ $a->patient?->full_name ?? '—' }}</td>
                        <td><code>{{ $a->patient?->patient_number ?? '—' }}</code></td>
                        <td>{{ $a->doctor ? 'Dr. ' . $a->doctor->full_name : '—' }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $a->status ?? 'scheduled')) }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('hospital.appointments.show', $a->id) }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-folder-open"></i> Open &amp; Certify
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No appointments awaiting certification.</td></tr>
                @endforelse
            </tbody>
        </table>
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