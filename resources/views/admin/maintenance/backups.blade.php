@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-database me-2"></i>Backup Manager</h4>
</div>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <form method="POST" action="{{ route('admin.maintenance.backup.create') }}">
                @csrf
                <input type="hidden" name="type" value="database">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-database me-2"></i>Backup Database
                </button>
            </form>
            <form method="POST" action="{{ route('admin.maintenance.backup.create') }}">
                @csrf
                <input type="hidden" name="type" value="files">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-folder me-2"></i>Backup Files
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Backup History</h5>
    </div>
    <div class="card-body">
        @if(count($backups) > 0)
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $backup)
                <tr>
                    <td>{{ $backup['name'] }}</td>
                    <td>{{ ucfirst($backup['type']) }}</td>
                    <td>
                        <span class="badge bg-{{ $backup['status'] === 'completed' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning') }}">
                            {{ $backup['status'] }}
                        </span>
                    </td>
                    <td>{{ $backup['file_size'] ?? 'N/A' }}</td>
                    <td>{{ $backup['created_at'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="text-muted text-center">No backups yet</p>
        @endif
    </div>
</div>
@endsection