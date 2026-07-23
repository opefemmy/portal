@extends('layouts.app')

@section('title', 'Payment Import Logs')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Payment Import Logs</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('bursar.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('bursar.payments.sync.index') }}">Payment Synchronization</a></li>
                <li class="breadcrumb-item active">Logs</li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Import History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable" id="logsTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $log->user->name ?? 'System' }}</td>
                                <td><span class="badge bg-info">{{ $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                                <td>
                                    @if($log->metadata)
                                        @php $metadata = json_decode($log->metadata, true); @endphp
                                        @if($metadata)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#logModal{{ $log->id }}">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <div class="modal fade" id="logModal{{ $log->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Import Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <pre>{{ json_encode($metadata, JSON_PRETTY_PRINT) }}</pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No import logs found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
