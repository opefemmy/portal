@extends('layouts.app')

@section('title', 'Adjust Stock')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-sliders-h me-2"></i>Adjust Stock</h4>
                    <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-sm btn-secondary" title="Back to drugs">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('hospital.pharmacy.adjust.store') }}">
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
                                <label class="form-label">Delta (use − to subtract) *</label>
                                <input type="number" name="delta" class="form-control" placeholder="e.g. +10 or −5" required>
                                @error('delta') <small class="text-danger">{{ $message }}</small> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason *</label>
                            <input type="text" name="reason" class="form-control"
                                   placeholder="e.g. Recount correction, damaged stock, returned to supplier"
                                   required>
                            @error('reason') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reference *</label>
                            <input type="text" name="reference" class="form-control"
                                   placeholder="e.g. Recount 2026-08, Damaged batch XYZ"
                                   required>
                            @error('reference') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Apply Adjustment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5><i class="fas fa-info-circle me-2"></i>About Adjustments</h5></div>
                <div class="card-body small">
                    <p>Use adjustments for recounts, write-offs of damaged stock, or any other correction.</p>
                    <p>Enter a positive number to add stock, or a negative number to remove stock. The system will refuse to drive stock below zero.</p>
                    <p>An audit movement of type <code>adjustment</code> is recorded for accountability.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection