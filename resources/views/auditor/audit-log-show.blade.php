@extends('layouts.app')

@section('title', 'Audit Log #'.$auditLog->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-history me-2"></i>Audit Log #{{ $auditLog->id }}</h4>
    <a href="{{ route('auditor.audit-logs') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Action Details</h5></div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th width="200">User</th><td>{{ $auditLog->user->name ?? '—' }} <small class="text-muted">({{ $auditLog->user->email ?? 'n/a' }})</small></td></tr>
                <tr><th>Module</th><td><span class="badge bg-info">{{ $auditLog->module }}</span></td></tr>
                <tr><th>Action</th><td>{{ $auditLog->action }}</td></tr>
                <tr><th>Description</th><td>{{ $auditLog->description }}</td></tr>
                <tr><th>Entity</th><td>{{ $auditLog->entity_type }} <small class="text-muted">#{{ $auditLog->entity_id }}</small></td></tr>
                <tr><th>Status</th><td><span class="badge bg-{{ $auditLog->status === 'success' ? 'success' : 'danger' }}">{{ ucfirst($auditLog->status ?? '—') }}</span></td></tr>
                <tr><th>IP Address</th><td><code>{{ $auditLog->ip_address }}</code></td></tr>
                <tr><th>Computer</th><td>{{ $auditLog->computer_name ?? '—' }}</td></tr>
                <tr><th>When</th><td>{{ $auditLog->created_at?->format('M d, Y H:i:s') }}</td></tr>
                @if($auditLog->error_message)
                    <tr><th>Error</th><td class="text-danger">{{ $auditLog->error_message }}</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

@if($auditLog->old_values || $auditLog->new_values)
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning text-dark"><strong>Old Values</strong></div>
            <div class="card-body"><pre class="mb-0 small">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</pre></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white"><strong>New Values</strong></div>
            <div class="card-body"><pre class="mb-0 small">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</pre></div>
        </div>
    </div>
</div>
@endif
@endsection
