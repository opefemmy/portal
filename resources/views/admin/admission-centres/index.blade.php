@extends('layouts.app')

@section('title', 'Admission Centres')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Admission Centres</h4>
    <a href="{{ route('admin.admission-centres.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Centre
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Applicants</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($centres as $centre)
                    <tr>
                        <td><code>{{ $centre->code }}</code></td>
                        <td><strong>{{ $centre->name }}</strong></td>
                        <td>{{ $centre->address ?? 'N/A' }}</td>
                        <td>{{ $centre->phone ?? 'N/A' }}</td>
                        <td>{{ $centre->email ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $centre->applicants->count() }}</span>
                        </td>
                        <td>
                            @if($centre->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.admission-centres.edit', $centre->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.admission-centres.toggle', $centre->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $centre->is_active ? 'warning' : 'success' }}" title="{{ $centre->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fas fa-{{ $centre->is_active ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.admission-centres.destroy', $centre->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this centre?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" {{ $centre->applicants->count() > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-building fa-2x text-muted mb-2 d-block"></i>
                            No admission centres found.
                            <a href="{{ route('admin.admission-centres.create') }}">Add the first centre</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
