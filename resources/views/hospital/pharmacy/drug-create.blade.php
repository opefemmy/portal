@extends('layouts.app')

@section('title', 'Add New Drug')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Add New Drug</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('hospital.pharmacy.drug.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Drug Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Drug Code *</label>
                                <input type="text" name="code" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Generic Name</label>
                                <input type="text" name="generic_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Form *</label>
                                <select name="form" class="form-select" required>
                                    <option value="">Select Form</option>
                                    <option value="Tablet">Tablet</option>
                                    <option value="Capsule">Capsule</option>
                                    <option value="Syrup">Syrup</option>
                                    <option value="Injection">Injection</option>
                                    <option value="Cream">Cream</option>
                                    <option value="Ointment">Ointment</option>
                                    <option value="Drops">Drops</option>
                                    <option value="Inhaler">Inhaler</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Strength</label>
                                <input type="text" name="strength" class="form-control" placeholder="e.g., 500mg">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Unit *</label>
                                <input type="text" name="unit" class="form-control" placeholder="e.g., tablet, ml, piece" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Cost Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₦</span>
                                    <input type="number" name="cost_price" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Selling Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">₦</span>
                                    <input type="number" name="selling_price" class="form-control" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Reorder Level *</label>
                                <input type="number" name="reorder_level" class="form-control" value="10" min="0" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requires_prescription" class="form-check-input" id="requires_prescription" value="1">
                                <label class="form-check-label" for="requires_prescription">
                                    Requires Doctor's Prescription
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Drug</button>
                            <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Info</h5>
                </div>
                <div class="card-body">
                    <p>Add drugs to your pharmacy inventory. Make sure to set appropriate reorder levels to avoid stockouts.</p>
                    <hr>
                    <h6>Tips:</h6>
                    <ul>
                        <li>Use unique drug codes</li>
                        <li>Set reorder levels based on usage</li>
                        <li>Mark drugs that require prescription</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
