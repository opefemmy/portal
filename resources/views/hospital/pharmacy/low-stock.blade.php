@extends('layouts.app')

@section('title', 'Low Stock Drugs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Low Stock Drugs</h2>
        </div>
    </div>

    @if($drugs->count() > 0)
    <div class="alert alert-warning">
        <strong>{{ $drugs->count() }} drug(s) are below reorder level!</strong>
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
                        <tr class="table-warning">
                            <td>{{ $drug->code }}</td>
                            <td>{{ $drug->name }}</td>
                            <td>{{ $drug->category->name ?? '-' }}</td>
                            <td class="text-danger fw-bold">{{ $drug->current_stock }}</td>
                            <td>{{ $drug->reorder_level }}</td>
                            <td>{{ $drug->unit }}</td>
                            <td>₦{{ number_format($drug->selling_price, 2) }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary">Add Stock</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">All drugs are sufficiently stocked</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
