@extends('layouts.app')

@section('title', 'Deleted Record #'.$deletedRecord->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-trash-restore me-2"></i>Deleted Record #{{ $deletedRecord->id }}</h4>
    <a href="{{ route('auditor.deleted') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Deletion Details</h5></div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th width="200">Deleted By</th><td>{{ $deletedRecord->user->name ?? '—' }}</td></tr>
                <tr><th>Table</th><td><code>{{ $deletedRecord->table_name }}</code></td></tr>
                <tr><th>Record ID</th><td>{{ $deletedRecord->record_id }}</td></tr>
                <tr><th>Reason</th><td>{{ $deletedRecord->deletion_reason ?? '—' }}</td></tr>
                <tr><th>IP Address</th><td><code>{{ $deletedRecord->ip_address }}</code></td></tr>
                <tr><th>When</th><td>{{ $deletedRecord->created_at?->format('M d, Y H:i:s') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-dark text-white"><strong>Snapshot of Record Data</strong></div>
    <div class="card-body"><pre class="mb-0 small">{{ json_encode($deletedRecord->record_data, JSON_PRETTY_PRINT) }}</pre></div>
</div>
@endsection
