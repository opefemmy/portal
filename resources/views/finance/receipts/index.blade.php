@extends('layouts.app')

@section('title', 'Receipts')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Receipts</h4>
    @can('finance.receipts.create')
    <a href="{{ route('finance.receipts.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Generate Receipt
    </a>
    @endcan
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Date</th>
                    <th>Verified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $receipt)
                <tr>
                    <td><strong>{{ $receipt->receipt_number }}</strong></td>
                    <td>{{ $receipt->student->name ?? 'N/A' }}</td>
                    <td>₦{{ number_format($receipt->amount, 2) }}</td>
                    <td>{{ ucfirst($receipt->payment_method) }}</td>
                    <td>{{ $receipt->payment_date->format('d M Y') }}</td>
                    <td>
                        @if($receipt->is_verified)
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('finance.receipts.show', $receipt->id) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        @can('finance.receipts.verify')
                            @if(!$receipt->is_verified)
                            <a href="{{ route('finance.receipts.verify', $receipt->id) }}" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i>
                            </a>
                            @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No receipts found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $receipts->links() }}
    </div>
</div>
@endsection