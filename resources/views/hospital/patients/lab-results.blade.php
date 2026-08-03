@extends('layouts.app')

@section('title', 'Lab Results — ' . $patient->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="fas fa-vial me-2"></i>Lab Results</h3>
            <small class="text-muted">{{ $patient->full_name }} · {{ $patient->patient_number }}</small>
        </div>
        <a href="{{ route('hospital.patients.show', $patient->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Requested</th><th>Test</th><th>Doctor</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($labRequests as $r)
                        <tr>
                            <td>{{ optional($r->requested_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td>{{ $r->test_type }}</td>
                            <td>Dr. {{ $r->doctor?->full_name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $r->status === 'completed' ? 'success' : ($r->status === 'pending' ? 'warning' : 'primary') }}">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('hospital.lab.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No lab requests on record.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-3">{{ $labRequests->links() }}</div>
        </div>
    </div>
</div>
@endsection