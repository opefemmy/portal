@extends('layouts.app')

@section('title', 'Low Stock Drugs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h2 class="mb-0">Low Stock Drugs</h2>
            <div class="d-flex gap-2 flex-wrap">
                @permission('pharmacy.receive')
                    <a href="{{ route('hospital.pharmacy.receive') }}" class="btn btn-success" title="Record stock received from supplier">
                        <i class="fas fa-truck-loading me-2"></i>Receive Stock
                    </a>
                @endpermission
                @permission('pharmacy.adjust')
                    <a href="{{ route('hospital.pharmacy.adjust') }}" class="btn btn-warning" title="Adjust stock">
                        <i class="fas fa-sliders-h me-2"></i>Adjust Stock
                    </a>
                @endpermission
            </div>
        </div>
    </div>

    @if($drugs->count() > 0)
    <div class="alert alert-warning">
        <strong>{{ $drugs->count() }} drug(s) are at or below reorder level!</strong>
        Pharmacists and store keepers have been notified automatically.
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Drug Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Unit</th>
                            <th>Selling Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drugs as $drug)
                        @php
                            $isOut = (int) $drug->current_stock <= 0;
                            $rowClass = $isOut ? 'table-danger' : 'table-warning';
                            $badgeClass = $isOut ? 'bg-danger' : 'bg-warning text-dark';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $drug->code }}</td>
                            <td>{{ $drug->name }}</td>
                            <td>{{ $drug->category->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ (int) $drug->current_stock }} {{ $isOut ? 'OUT' : 'LOW' }}
                                </span>
                            </td>
                            <td>{{ $drug->reorder_level }}</td>
                            <td>{{ $drug->unit }}</td>
                            <td>₦{{ number_format($drug->selling_price, 2) }}</td>
                            <td>
                                @permission('pharmacy.receive')
                                    <a href="{{ route('hospital.pharmacy.receive') }}?drug_id={{ $drug->id }}" class="btn btn-sm btn-success" title="Receive stock for {{ $drug->name }}">
                                        <i class="fas fa-truck-loading me-1"></i>Receive
                                    </a>
                                @endpermission
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-success">
                                <i class="fas fa-check-circle me-2"></i>All drugs are sufficiently stocked.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection