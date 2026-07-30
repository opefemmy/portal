@extends('layouts.app')

@section('title', 'Expiring Drugs')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="mb-4">Expiring Drugs (Next 30 Days)</h2>
        </div>
    </div>

    @if($expiringBatches->count() > 0)
    <div class="alert alert-danger">
        <strong>{{ $expiringBatches->count() }} batch(es) expiring soon!</strong>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Drug</th>
                            <th>Batch No</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Days Left</th>
                            <th>Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expiringBatches as $batch)
                        <tr class="{{ \Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now()) <= 7 ? 'table-danger' : 'table-warning' }}">
                            <td>{{ $batch->drug->name ?? 'N/A' }}</td>
                            <td>{{ $batch->batch_number }}</td>
                            <td>{{ $batch->quantity }}</td>
                            <td>{{ \Carbon\Carbon::parse($batch->expiry_date)->format('d M Y') }}</td>
                            <td>
                                @php
                                $daysLeft = \Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now());
                                @endphp
                                <span class="badge bg-{{ $daysLeft <= 7 ? 'danger' : 'warning' }}">
                                    {{ $daysLeft }} days
                                </span>
                            </td>
                            <td>₦{{ number_format($batch->unit_cost * $batch->quantity, 2) }}</td>
                            <td>
                                <button class="btn btn-sm btn-info">Use First</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No expiring drugs in the next 30 days</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
