@extends('layouts.app')

@section('title', 'External Patients')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-user-friends me-2"></i>External Patients Management</h4>
    @permission('external-patients.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal" title="Register a new external patient">
            <i class="fas fa-plus me-2"></i>Register New Patient
        </button>
    @endpermission
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <h3>{{ \App\Models\Hospital\ExternalPatient::count() }}</h3>
                <small class="text-muted">Total Patients</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body text-center">
                <h3>{{ \App\Models\Hospital\ExternalPatient::whereDate('created_at', today())->count() }}</h3>
                <small class="text-muted">Today Registered</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <h3>{{ \App\Models\Hospital\HospitalVisit::whereDate('visit_date', today())->count() }}</h3>
                <small class="text-muted">Today's Visits</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h3>{{ \App\Models\Hospital\ExternalAppointment::whereDate('appointment_date', '>=', now())->where('status', 'scheduled')->count() }}</h3>
                <small class="text-muted">Upcoming Appointments</small>
            </div>
        </div>
    </div>
</div>

<!-- Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('hospital.external-patients.index') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search by name, patient number, or phone..." value="{{ $search ?? '' }}">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            @if($search)
            <a href="{{ route('hospital.external-patients.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<!-- Patients Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Patient Number</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Gender</th>
                        <th>Age</th>
                        <th>Last Visit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td><strong>{{ $patient->patient_number }}</strong></td>
                        <td>{{ $patient->full_name }}</td>
                        <td>{{ $patient->phone }}</td>
                        <td>{{ $patient->email ?? 'N/A' }}</td>
                        <td>{{ ucfirst($patient->gender ?? 'N/A') }}</td>
                        <td>{{ $patient->age ?? 'N/A' }}</td>
                        <td>
                            @if($patient->latestVisit)
                            <span class="badge bg-info">{{ optional($patient->latestVisit->visit_date)->format('d M Y') ?? 'N/A' }}</span>
                            @else
                            <span class="badge bg-secondary">No visits</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('hospital.external-patients.show', $patient->id) }}" class="btn btn-sm btn-outline-primary" title="View patient details and history">
                                <i class="fas fa-eye"></i> View
                            </a>
                            @permission('external-patients.create')
                                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addPatientModal" title="Register a new external patient">
                                    <i class="fas fa-plus"></i>
                                </button>
                            @endpermission
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <p class="text-muted mb-0">No patients found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $patients->links() }}
    </div>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New External Patient</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('hospital.external-patients.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Register Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
