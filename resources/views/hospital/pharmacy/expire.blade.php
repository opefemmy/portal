@extends('layouts.app')

@section('title', 'Write Off Expired Stock')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-calendar-times me-2"></i>Write Off Expired Stock</h4>
                    <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-sm btn-secondary" title="Back to drugs">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('hospital.pharmacy.expire.store') }}">
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
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference *</label>
                            <input type="text" name="reference" class="form-control"
                                   placeholder="e.g. Expired batch #B-2025-04 — written off 2026-08-01"
                                   required>
                            @error('reference') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="alert alert-warning small">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Expired stock is removed from <code>current_stock</code> and recorded as a
                            <code>movement_type = expired</code> audit row. This action cannot be undone.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt"></i> Write Off
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5><i class="fas fa-info-circle me-2"></i>Audit Trail</h5></div>
                <div class="card-body small">
                    <p>Every expired-stock write-off is recorded with the user, timestamp, and quantity so we always have an audit trail for shrinkage.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection