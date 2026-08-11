@extends('layouts.app')

@section('title', 'Records Audit Log')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-history me-2"></i>Records Audit Log</h4>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hospital.records.index') }}">Records</a></li>
        <li class="breadcrumb-item active">Audit</li>
    </ul>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Patient ID</label>
                <input type="number" name="patient_id" value="{{ request('patient_id') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label">Action contains</label>
                <input type="text" name="action" value="{{ request('action') }}" class="form-control" placeholder="e.g. archive">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                <a href="{{ route('hospital.records.audit') }}" class="btn btn-link">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-sm">
            <thead>
                <tr><th>When</th><th>User</th><th>Role</th><th>Action</th><th>Subject</th><th>Patient</th><th>IP</th></tr>
            </thead>
            <tbody>
                @forelse($entries as $row)
                    <tr>
                        <td>{{ $row->created_at->format('d M Y H:i:s') }}</td>
                        <td>{{ optional($row->user)->name ?? '—' }}</td>
                        <td><span class="badge bg-secondary">{{ $row->user_role ?? '—' }}</span></td>
                        <td><code>{{ $row->action }}</code></td>
                        <td>{{ class_basename($row->subject_type ?? '') }} #{{ $row->subject_id ?? '—' }}</td>
                        <td>{{ optional($row->patient)->full_name ?? '—' }}</td>
                        <td><small>{{ $row->ip_address }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No audit entries match.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $entries->withQueryString()->links() }}
    </div>
</div>
@endsection