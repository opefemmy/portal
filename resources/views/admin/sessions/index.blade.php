@extends('layouts.app')

@section('title', 'Sessions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Sessions</h4>
    <a href="{{ route('admin.sessions.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Session
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Semester</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    <tr>
                        <td>{{ $session->name }}</td>
                        <td>{{ $session->semester ?? 'N/A' }}</td>
                        <td>{{ $session->start_date?->format('d M Y') }}</td>
                        <td>{{ $session->end_date?->format('d M Y') }}</td>
                        <td>
                            @if($session->is_current)
                            <span class="badge bg-success">Current</span>
                            @else
                            <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            @if(!$session->is_current)
                            <form method="POST" action="{{ route('admin.sessions.set_current', $session) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Set this as the current session">Set Current</button>
                            </form>
                            @endif
                            <a href="{{ route('admin.sessions.edit', $session) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this session">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection