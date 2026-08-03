@extends('layouts.app')

@section('title', 'Prescription Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Prescription #{{ $prescription->id }}</h4>
                    <span class="badge bg-{{ $prescription->status === 'pending' ? 'warning' : 'success' }} fs-6">
                        {{ ucfirst($prescription->status) }}
                    </span>
                </div>
                <div class="card-body">
                    <!-- Patient Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Patient Information</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>Name:</th>
                                    <td>{{ $prescription->patient->full_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Gender:</th>
                                    <td>{{ ucfirst($prescription->patient->gender ?? '') ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Age:</th>
                                    <td>{{ $prescription->patient->age ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td>{{ $prescription->patient->phone ?? 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Prescription Details</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>Doctor:</th>
                                    <td>Dr. {{ $prescription->doctor->full_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Date:</th>
                                    <td>{{ optional($prescription->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</td>
                                </tr>
                                @if($prescription->dispensed_at)
                                <tr>
                                    <th>Dispensed:</th>
                                    <td>{{ $prescription->dispensed_at->format('d M Y, h:i A') }}</td>
                                </tr>
                                <tr>
                                    <th>Dispensed By:</th>
                                    <td>{{ $prescription->dispensedBy->full_name ?? 'N/A' }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($prescription->notes)
                    <div class="alert alert-info">
                        <strong>Doctor's Notes:</strong><br>
                        {{ $prescription->notes }}
                    </div>
                    @endif

                    <!-- Prescription Items -->
                    <h5>Prescribed Drugs</h5>
                    <form method="POST" action="{{ route('hospital.pharmacy.prescription.dispense', $prescription) }}">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Drug</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Duration</th>
                                        <th>Quantity</th>
                                        <th>In Stock</th>
                                        <th>
                                            @if($prescription->status === 'pending')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="selectAll">
                                                <label class="form-check-label" for="selectAll">Dispense</label>
                                            </div>
                                            @else
                                            Dispensed
                                            @endif
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($prescription->items as $item)
                                    <tr class="{{ !$item->drug ? 'table-danger' : '' }}">
                                        <td>
                                            {{ $item->drug->name ?? 'Drug not found' }}
                                            @if($item->drug && $item->drug->requires_prescription)
                                            <span class="badge bg-info">Rx</span>
                                            @endif
                                        </td>
                                        <td>{{ $item->dosage ?? '-' }}</td>
                                        <td>{{ $item->frequency ?? '-' }}</td>
                                        <td>{{ $item->duration ?? '-' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            @if($item->drug)
                                            <span class="{{ $item->drug->current_stock < $item->quantity ? 'text-danger fw-bold' : '' }}">
                                                {{ $item->drug->current_stock }} {{ $item->drug->unit }}
                                            </span>
                                            @else
                                            <span class="text-danger">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($prescription->status === 'pending')
                                            <div class="form-check">
                                                <input type="checkbox" name="items[{{ $item->id }}][dispensed]"
                                                       class="form-check-input dispense-checkbox"
                                                       value="1"
                                                       {{ !$item->drug || $item->drug->current_stock < $item->quantity ? 'disabled' : '' }}>
                                            </div>
                                            @else
                                            <i class="fas fa-{{ $item->is_dispensed ? 'check text-success' : 'times text-danger' }}"></i>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No items in this prescription</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($prescription->status === 'pending')
                        <div class="mt-3">
                            <div class="mb-3">
                                <label class="form-label">Pharmacist Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Dispense Prescription
                            </button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Quick Stock Info -->
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Stock Alerts</h5>
                </div>
                <div class="card-body">
                    @php
                    $lowStockItems = $prescription->items->filter(function($item) {
                        return $item->drug && $item->drug->current_stock <= $item->drug->reorder_level;
                    });
                    @endphp

                    @if($lowStockItems->count() > 0)
                    <div class="alert alert-warning">
                        <strong>{{ $lowStockItems->count() }} drug(s) are low on stock:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($lowStockItems as $item)
                            <li>{{ $item->drug->name }} ({{ $item->drug->current_stock }} left)</li>
                            @endforeach
                        </ul>
                    </div>
                    @else
                    <div class="alert alert-success">
                        All prescribed drugs are in stock.
                    </div>
                    @endif
                </div>
            </div>

            <!-- Print -->
            <div class="card">
                <div class="card-body">
                    <a href="#" class="btn btn-secondary w-100 mb-2" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Prescription
                    </a>
                    <a href="{{ route('hospital.pharmacy.prescriptions') }}" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left me-2"></i>Back to Prescriptions
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.dispense-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                if (!cb.disabled) {
                    cb.checked = this.checked;
                }
            });
        });
    }
});
</script>
@endsection
