@extends('layouts.app')

@section('title', 'Inventory')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-pills me-2"></i>Inventory</h4>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hospital.admin.dashboard') }}">Admin</a></li>
        <li class="breadcrumb-item active">Inventory</li>
    </ul>
</div>

<div class="row mb-3">
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Stock value</p>
        <h3>₦{{ number_format($totalStockValue, 0) }}</h3>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Low-stock items</p>
        <h3 class="text-{{ $lowStock->count() > 0 ? 'warning' : 'success' }}">{{ $lowStock->count() }}</h3>
    </div></div></div>
    <div class="col-md-3"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">Batches expiring (60d)</p>
        <h3 class="text-{{ $expiringSoon->count() > 0 ? 'warning' : 'success' }}">{{ $expiringSoon->count() }}</h3>
    </div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock</h5></div>
    <div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>Drug</th><th>Form</th><th class="text-end">Stock</th><th class="text-end">Reorder Level</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($lowStock as $drug)
                    <tr>
                        <td><strong>{{ $drug->name }}</strong><br><small class="text-muted">{{ $drug->generic_name }}</small></td>
                        <td>{{ ucfirst($drug->form ?? '—') }} {{ $drug->strength }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $drug->current_stock <= 0 ? 'danger' : 'warning' }}">{{ $drug->current_stock }}</span>
                        </td>
                        <td class="text-end">{{ $drug->reorder_level }}</td>
                        <td>{!! $drug->isOutOfStock() ? '<span class="badge bg-danger">Out</span>' : '<span class="badge bg-warning">Low</span>' !!}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">All drug stock is healthy.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title"><i class="fas fa-clock me-2"></i>Expiring Batches (next 60 days)</h5></div>
    <div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>Drug</th><th>Batch</th><th class="text-end">Qty</th><th>Expiry</th><th>Days Left</th></tr></thead>
            <tbody>
                @forelse($expiringSoon as $batch)
                    @php $daysLeft = \Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now()); @endphp
                    <tr>
                        <td><strong>{{ optional($batch->drug)->name }}</strong></td>
                        <td><code>{{ $batch->batch_number }}</code></td>
                        <td class="text-end">{{ $batch->remaining_quantity }}</td>
                        <td>{{ \Carbon\Carbon::parse($batch->expiry_date)->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $daysLeft < 14 ? 'danger' : 'warning' }}">{{ $daysLeft }}d</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No batches expiring soon.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection