@extends('layouts.app')

@section('title', 'Receive Stock')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-truck-loading me-2"></i>Receive Stock</h4>
                    <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-sm btn-secondary" title="Back to drugs">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('hospital.pharmacy.receive.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Drug *</label>
                                <select name="drug_id" class="form-select" required>
                                    <option value="">— Select drug —</option>
                                    @foreach($drugs as $drug)
                                        <option value="{{ $drug->id }}">
                                            {{ $drug->name }}
                                            (current stock: {{ (int) $drug->current_stock }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('drug_id') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantity *</label>
                                <input type="number" name="quantity" class="form-control" min="1" required>
                                @error('quantity') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Unit cost (₦)</label>
                                <input type="number" step="0.01" min="0" name="unit_cost" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference / Supplier *</label>
                            <input type="text" name="reference" class="form-control"
                                   placeholder="e.g. Supplier invoice #INV-2026-08-001"
                                   required>
                            @error('reference') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus-circle"></i> Receive Stock
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5><i class="fas fa-info-circle me-2"></i>About Receive</h5></div>
                <div class="card-body small">
                    <p>Use this form to record stock received from a supplier or transferred in from another location.</p>
                    <p>The quantity is added to <code>current_stock</code> and an audit movement of type
                       <code>purchase</code> is written to the inventory log.</p>
                    <p>If the resulting stock is at or below the reorder level, pharmacists and store keepers receive a low-stock alert.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection