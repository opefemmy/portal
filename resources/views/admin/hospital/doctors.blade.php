@extends('layouts.admin')

@section('title', 'Medical Doctors List')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-user-md me-2"></i>Medical Doctors</h4>
    <p class="text-muted">List of active medical doctors in the facility.</p>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">Active Doctors</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Staff Number</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doctor)
                    <tr>
                        <td>{{ $doctor->full_name }}</td>
                        <td>{{ $doctor->staff_number }}</td>
                        <td>{{ $doctor->phone }}</td>
                        <td>{{ $doctor->email }}</td>
                        <td>
                            <span class="badge bg-{{ $doctor->is_available ? 'success' : 'warning' }}">
                                {{ $doctor->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.staff.show', $doctor->user_id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">No active doctors found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection