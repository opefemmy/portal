@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-exchange-alt me-2"></i>Transactions</h4>
    <a href="{{ route('finance.transactions.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Transaction</a>
</div>
@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Date</th><th>Txn #</th><th>Type</th><th>Ledger</th><th>Description</th><th class="text-end">Amount</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($transactions ?? [] as $t)
                <tr>
                    <td>{{ optional($t->transaction_date)->format('M d, Y') ?? $t->created_at?->format('M d, Y') }}</td>
                    <td><strong>{{ $t->transaction_number ?? '#'.$t->id }}</strong></td>
                    <td><span class="badge bg-{{ $t->type === 'credit' ? 'success' : 'danger' }}">{{ ucfirst($t->type) }}</span></td>
                    <td>{{ $t->ledger->name ?? '—' }}</td>
                    <td>{{ $t->description ?? '—' }}</td>
                    <td class="text-end">₦{{ number_format($t->amount, 2) }}</td>
                    <td><a href="{{ route('finance.transactions.show', $t) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">No transactions yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="mt-3">{{ ($transactions ?? null)?->appends(request()->query())->links() }}</div>
</div></div>
@endsection
