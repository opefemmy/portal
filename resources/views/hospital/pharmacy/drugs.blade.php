@extends('layouts.app')

@section('title', 'Drug Inventory')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Drug Inventory</h2>
                <a href="{{ route('hospital.pharmacy.drugs.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Drug
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search drugs..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check">
                        <input type="checkbox" name="low_stock" class="form-check-input" id="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }}>
                        <label class="form-check-label" for="low_stock">Low Stock Only</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('hospital.pharmacy.drugs') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Drugs Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Form</th>
                            <th>Strength</th>
                            <th>Stock</th>
                            <th>Unit</th>
                            <th>Cost Price</th>
                            <th>Selling Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($drugs as $drug)
                        <tr class="{{ $drug->current_stock <= $drug->reorder_level ? 'table-warning' : '' }}">
                            <td>{{ $drug->code }}</td>
                            <td>{{ $drug->name }}</td>
                            <td>{{ $drug->generic_name ?? '-' }}</td>
                            <td>{{ $drug->category->name ?? '-' }}</td>
                            <td>{{ $drug->form }}</td>
                            <td>{{ $drug->strength ?? '-' }}</td>
                            <td>
                                <span class="{{ $drug->current_stock <= $drug->reorder_level ? 'text-danger fw-bold' : '' }}">
                                    {{ $drug->current_stock }}
                                </span>
                            </td>
                            <td>{{ $drug->unit }}</td>
                            <td>₦{{ number_format($drug->cost_price, 2) }}</td>
                            <td>₦{{ number_format($drug->selling_price, 2) }}</td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#drugModal{{ $drug->id }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="#" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>

                        <!-- Drug Detail Modal -->
                        <div class="modal fade" id="drugModal{{ $drug->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">{{ $drug->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table table-borderless">
                                            <tr>
                                                <th>Code:</th>
                                                <td>{{ $drug->code }}</td>
                                            </tr>
                                            <tr>
                                                <th>Generic Name:</th>
                                                <td>{{ $drug->generic_name ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Category:</th>
                                                <td>{{ $drug->category->name ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Form:</th>
                                                <td>{{ $drug->form }}</td>
                                            </tr>
                                            <tr>
                                                <th>Strength:</th>
                                                <td>{{ $drug->strength ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Current Stock:</th>
                                                <td class="{{ $drug->current_stock <= $drug->reorder_level ? 'text-danger' : '' }}">
                                                    {{ $drug->current_stock }} {{ $drug->unit }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Reorder Level:</th>
                                                <td>{{ $drug->reorder_level }} {{ $drug->unit }}</td>
                                            </tr>
                                            <tr>
                                                <th>Cost Price:</th>
                                                <td>₦{{ number_format($drug->cost_price, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Selling Price:</th>
                                                <td>₦{{ number_format($drug->selling_price, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <th>Requires Prescription:</th>
                                                <td>{{ $drug->requires_prescription ? 'Yes' : 'No' }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center">No drugs found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $drugs->links() }}
        </div>
    </div>
</div>
@endsection
