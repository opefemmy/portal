@extends('layouts.app')

@section('title', 'My Prescriptions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Prescriptions</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Medical Portal
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Doctor</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prescriptions as $prescription)
                <tr>
                    <td>{{ $prescription->created_at->format('d M Y') }}</td>
                    <td>Dr. {{ $prescription->doctor->last_name ?? 'N/A' }}</td>
                    <td>{{ $prescription->items->count() }} item(s)</td>
                    <td>
                        @switch($prescription->status)
                            @case('pending')
                                <span class="badge bg-warning">Pending</span>
                                @break
                            @case('dispensed')
                                <span class="badge bg-success">Dispensed</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ $prescription->status }}</span>
                        @endswitch
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#prescriptionModal{{ $prescription->id }}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </td>
                </tr>

                <!-- Prescription Detail Modal -->
                <div class="modal fade" id="prescriptionModal{{ $prescription->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Prescription - {{ $prescription->created_at->format('d M Y') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <p><strong>Prescribed by:</strong> Dr. {{ $prescription->doctor->first_name ?? '' }} {{ $prescription->doctor->last_name ?? '' }}</p>
                                    <p><strong>Status:</strong>
                                        @switch($prescription->status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('dispensed')
                                                <span class="badge bg-success">Dispensed</span>
                                                @break
                                            @default
                                                <span class="badge bg-secondary">{{ $prescription->status }}</span>
                                        @endswitch
                                    </p>
                                </div>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Medication</th>
                                            <th>Dosage</th>
                                            <th>Frequency</th>
                                            <th>Duration</th>
                                            <th>Instructions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($prescription->items as $item)
                                        <tr>
                                            <td>{{ $item->medication }}</td>
                                            <td>{{ $item->dosage }}</td>
                                            <td>{{ $item->frequency }}</td>
                                            <td>{{ $item->duration }}</td>
                                            <td>{{ $item->instructions ?? 'N/A' }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No items in this prescription</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                @if($prescription->notes)
                                <div class="mt-3">
                                    <p><strong>Notes:</strong></p>
                                    <p>{{ $prescription->notes }}</p>
                                </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No prescriptions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $prescriptions->links() }}
    </div>
</div>
@endsection