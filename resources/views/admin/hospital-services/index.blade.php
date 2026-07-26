@extends('layouts.app')

@section('title', 'Hospital Services Management')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-hospital me-2"></i>Hospital Services Management</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
        <i class="fas fa-plus me-2"></i>Add New Service
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body text-center">
                <h3>{{ $services->count() }}</h3>
                <small class="text-muted">Total Services</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body text-center">
                <h3>{{ $services->where('is_active', true)->count() }}</h3>
                <small class="text-muted">Active Services</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body text-center">
                <h3>{{ $services->where('requires_appointment', true)->count() }}</h3>
                <small class="text-muted">Requires Appointment</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h3>₦{{ number_format($services->sum('amount')) }}</h3>
                <small class="text-muted">Total Value</small>
            </div>
        </div>
    </div>
</div>

<!-- Services by Category -->
@php
$groupedServices = $services->groupBy('category');
@endphp

@foreach($groupedServices as $category => $categoryServices)
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-folder me-2"></i>{{ $category }}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Service Name</th>
                        <th>Amount</th>
                        <th>Requires Appointment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryServices as $service)
                    <tr>
                        <td><strong>{{ $service->name }}</strong></td>
                        <td>₦{{ number_format($service->amount) }}</td>
                        <td>
                            @if($service->requires_appointment)
                            <span class="badge bg-warning">Yes</span>
                            @else
                            <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            @if($service->is_active)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.hospital-services.destroy', $service->id) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Edit Service</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.hospital-services.update', $service->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Service Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Category</label>
                                            <select name="category" class="form-select" required>
                                                @foreach(['Registration', 'Consultation', 'Laboratory', 'Pharmacy', 'Radiology', 'Admission', 'Others'] as $cat)
                                                <option value="{{ $cat }}" {{ $service->category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount (₦)</label>
                                            <input type="number" name="amount" class="form-control" value="{{ $service->amount }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="requires_appointment" class="form-check-input" id="edit_appointment_{{ $service->id }}" {{ $service->requires_appointment ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_appointment_{{ $service->id }}">Requires Appointment</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="edit_active_{{ $service->id }}" {{ $service->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_active_{{ $service->id }}">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Update Service</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Add New Hospital Service</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.hospital-services.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Malaria Test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Registration">Registration</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Radiology">Radiology (X-Ray/Scan)</option>
                            <option value="Admission">Admission</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="requires_appointment" class="form-check-input" id="requires_appointment">
                            <label class="form-check-label" for="requires_appointment">Requires Appointment</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" checked>
                            <label class="form-check-label" for="is_active">Active (Available for payment)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
