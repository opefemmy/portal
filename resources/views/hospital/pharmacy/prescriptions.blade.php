@extends('layouts.app')

@section('title', 'Prescriptions')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Prescriptions</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ !request('status') || request('status') === 'pending' ? 'active' : '' }}"
                       href="{{ route('hospital.pharmacy.prescriptions', ['status' => 'pending']) }}">
                        Pending
                        <span class="badge bg-warning">{{ \App\Models\Hospital\HospitalPrescription::where('status', 'pending')->count() }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') === 'dispensed' ? 'active' : '' }}"
                       href="{{ route('hospital.pharmacy.prescriptions', ['status' => 'dispensed']) }}">
                        Dispensed
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request('status') === 'all' ? 'active' : '' }}"
                       href="{{ route('hospital.pharmacy.prescriptions', ['status' => 'all']) }}">
                        All
                    </a>
                </li>
            </ul>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($prescriptions as $prescription)
                        <tr>
                            <td>#{{ $prescription->id }}</td>
                            <td>{{ $prescription->patient->name ?? 'N/A' }}</td>
                            <td>{{ $prescription->doctor->name ?? 'N/A' }}</td>
                            <td>{{ optional($prescription->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</td>
                            <td>{{ $prescription->items->count() }} items</td>
                            <td>
                                <span class="badge bg-{{ $prescription->status === 'pending' ? 'warning' : ($prescription->status === 'dispensed' ? 'success' : 'info') }}">
                                    {{ ucfirst(str_replace('_', ' ', $prescription->status)) }}
                                </span>
                            </td>
                            <td>
                                @permission('prescriptions.dispense')
                                    <a href="{{ route('hospital.pharmacy.prescriptions.show', $prescription) }}" class="btn btn-sm btn-primary" title="View prescription details and dispense drugs">
                                        <i class="fas fa-eye"></i> View & Dispense
                                    </a>
                                @else
                                    <a href="{{ route('hospital.pharmacy.prescriptions.show', $prescription) }}" class="btn btn-sm btn-outline-primary" title="View prescription details">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endpermission
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No prescriptions found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $prescriptions->links() }}
        </div>
    </div>
</div>
@endsection
