@extends('layouts.app')

@section('title', 'Budgets')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-budget me-2"></i>Budgets</h4>
    @can('finance.budgets.create')
        <a href="{{ route('finance.budgets.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Budget
        </a>
    @endcan
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="GET" action="{{ route('finance.budgets.index') }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Fiscal Year</label>
        <input type="text" name="fiscal_year" value="{{ request('fiscal_year') }}" class="form-control form-control-sm" placeholder="2026">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach(['draft','pending','approved','active','closed','rejected'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-grid">
        <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button>
    </div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Fiscal Year</th>
                        <th>Department</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Spent</th>
                        <th class="text-end">Balance</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($budgets as $b)
                        <tr>
                            <td><strong>{{ $b->name }}</strong></td>
                            <td>{{ $b->fiscal_year }}</td>
                            <td>{{ $b->department->name ?? '—' }}</td>
                            <td class="text-end">₦{{ number_format($b->total_budget, 2) }}</td>
                            <td class="text-end">₦{{ number_format($b->total_spent ?? 0, 2) }}</td>
                            <td class="text-end">₦{{ number_format($b->balance ?? 0, 2) }}</td>
                            <td>
                                <small>{{ optional($b->start_date)->format('M d, Y') }}<br>→ {{ optional($b->end_date)->format('M d, Y') }}</small>
                            </td>
                            <td>
                                @php
                                    $badges = ['draft'=>'secondary','pending'=>'warning','approved'=>'info','active'=>'success','closed'=>'dark','rejected'=>'danger'];
                                @endphp
                                <span class="badge bg-{{ $badges[$b->status] ?? 'secondary' }}">{{ ucfirst($b->status ?? 'draft') }}</span>
                            </td>
                            <td>
                                <a href="{{ route('finance.budgets.show', $b->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center py-4 text-muted">No budgets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $budgets->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
