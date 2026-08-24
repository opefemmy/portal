@extends('layouts.app')

@section('title', $budget->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-budget me-2"></i>{{ $budget->name }}</h4>
    <div>
        @if($budget->status === 'draft')
            <form method="POST" action="{{ route('finance.budgets.approve', $budget) }}" class="d-inline">
                @csrf
                <button class="btn btn-success"><i class="fas fa-check me-2"></i>Approve</button>
            </form>
        @elseif($budget->status === 'approved')
            <form method="POST" action="{{ route('finance.budgets.activate', $budget) }}" class="d-inline">
                @csrf
                <button class="btn btn-primary"><i class="fas fa-play me-2"></i>Activate</button>
            </form>
        @endif
        <a href="{{ route('finance.budgets.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3"><small>Total</small><h4>₦{{ number_format($budget->total_budget, 2) }}</h4></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small>Spent</small><h4 class="text-danger">₦{{ number_format($budget->total_spent ?? 0, 2) }}</h4></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small>Balance</small><h4 class="text-success">₦{{ number_format($budget->balance ?? 0, 2) }}</h4></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small>Status</small><h4><span class="badge bg-{{ ['draft'=>'secondary','pending'=>'warning','approved'=>'info','active'=>'success','closed'=>'dark'][$budget->status] ?? 'secondary' }}">{{ ucfirst($budget->status) }}</span></h4></div></div>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Details</h5></div>
    <div class="card-body">
        <table class="table table-borderless mb-0">
            <tbody>
                <tr><th width="200">Fiscal Year</th><td>{{ $budget->fiscal_year }}</td></tr>
                <tr><th>Department</th><td>{{ $budget->department->name ?? 'Institution-wide' }}</td></tr>
                <tr><th>Period</th><td>{{ optional($budget->start_date)->format('M d, Y') }} → {{ optional($budget->end_date)->format('M d, Y') }}</td></tr>
                <tr><th>Approved By</th><td>{{ $budget->approvedBy->name ?? '—' }} on {{ optional($budget->approved_at)->format('M d, Y H:i') ?? '—' }}</td></tr>
                <tr><th>Notes</th><td>{{ $budget->notes ?? '—' }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

@if(($allocations ?? collect())->count())
<div class="card">
    <div class="card-header bg-info text-white"><h5 class="mb-0">Allocations</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Ledger</th><th class="text-end">Allocated</th><th class="text-end">Balance</th></tr></thead>
            <tbody>
                @foreach($allocations as $a)
                    <tr>
                        <td>{{ $a->ledger->name ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($a->allocated_amount, 2) }}</td>
                        <td class="text-end">₦{{ number_format($a->balance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
