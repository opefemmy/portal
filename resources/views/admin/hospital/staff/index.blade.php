@extends('layouts.app')

@section('title', 'Manage Hospital Staff')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Hospital Staff Directory</h4>
    <a href="{{ route('admin.hospital-staff.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add New Staff
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Staff Types</option>
                    @foreach($staffTypes as $key => $label)
                        <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <input type="text" name="search" class="form-control" placeholder="Search by name, number or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Staff No.</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staff as $member)
                    <tr>
                        <td>{{ $member->staff_number }}</td>
                        <td>
                            <div class="fw-bold">{{ $member->first_name }} {{ $member->last_name }}</div>
                            <small class="text-muted">{{ $member->user->email ?? 'No email' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">{{ $staffTypes[$member->staff_type] ?? $member->staff_type }}</span>
                        </td>
                        <td>{{ $member->phone ?? 'N/A' }}</td>
                        <td>
                            @if($member->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.hospital-staff.edit', $member->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.hospital-staff.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this staff member? This will also remove their user account.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No hospital staff found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $staff->links() }}
    </div>
</div>
@endsection