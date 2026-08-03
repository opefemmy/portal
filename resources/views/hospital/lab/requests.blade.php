@extends('layouts.app')

@section('title', 'Lab Requests')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="fas fa-vial me-2"></i>Lab Requests</h3>
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="sample_collected" {{ request('status') === 'sample_collected' ? 'selected' : '' }}>Sample Collected</option>
                <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
            <input type="date" name="date" class="form-control" value="{{ request('date') }}" onchange="this.form.submit()">
        </form>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Patient</th><th>Test</th><th>Doctor</th><th>Requested</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td>#{{ $r->id }}</td>
                            <td>
                                {{ $r->patient?->full_name ?? '—' }}<br>
                                <small class="text-muted">{{ $r->patient?->patient_number ?? '' }}</small>
                            </td>
                            <td>{{ $r->test_type }}</td>
                            <td>Dr. {{ $r->doctor?->full_name ?? '—' }}</td>
                            <td>{{ optional($r->requested_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $r->status === 'completed' ? 'success' : ($r->status === 'pending' ? 'warning' : 'primary') }}">
                                    {{ ucfirst(str_replace('_',' ',$r->status)) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('hospital.lab.show', $r->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No lab requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-3">{{ $requests->links() }}</div>
        </div>
    </div>
</div>
@endsection