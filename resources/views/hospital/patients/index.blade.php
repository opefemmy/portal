@extends('layouts.app')

@section('title', 'Hospital Patients')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Patients Registry</h3>
        <a href="{{ route('hospital.patients.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Register Patient
        </a>
    </div>

    <!-- Search & Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, number, phone..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="patient_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="student" {{ request('patient_type') == 'student' ? 'selected' : '' }}>Student</option>
                        <option value="staff" {{ request('patient_type') == 'staff' ? 'selected' : '' }}>Staff</option>
                        <option value="visitor" {{ request('patient_type') == 'visitor' ? 'selected' : '' }}>Visitor</option>
                        <option value="dependent" {{ request('patient_type') == 'dependent' ? 'selected' : '' }}>Dependent</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('hospital.patients.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Patient No.</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Phone</th>
                        <th>Type</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td><strong>{{ $patient->patient_number }}</strong></td>
                        <td>{{ $patient->full_name }}</td>
                        <td>{{ ucfirst($patient->gender) }}</td>
                        <td>{{ $patient->age }}</td>
                        <td>{{ $patient->phone }}</td>
                        <td>
                            <span class="badge bg-{{ $patient->patient_type === 'student' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($patient->patient_type) }}
                            </span>
                        </td>
                        <td>{{ $patient->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('hospital.patients.show', $patient->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('hospital.patients.timeline', $patient->id) }}" class="btn btn-sm btn-secondary">
                                <i class="fas fa-history"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No patients found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $patients->links() }}
        </div>
    </div>
</div>
@endsection