@extends('layouts.app')

@section('title', 'Finance Dashboard')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Finance Dashboard</h4>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Today Income</h5>
                    <h2>₦{{ number_format($stats['today_income'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Monthly Income</h5>
                    <h2>₦{{ number_format($stats['monthly_income'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Outstanding Invoices</h5>
                    <h2>₦{{ number_format($stats['outstanding_balance'], 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Active Budgets</h5>
                    <h2>{{ $stats['active_budgets'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $txn)
                            <tr>
                                <td>{{ $txn->transaction_date->format('d M') }}</td>
                                <td>{{ Str::limit($txn->description, 30) }}</td>
                                <td>
                                    <span class="badge bg-{{ $txn->type === 'credit' ? 'success' : 'danger' }}">
                                        {{ ucfirst($txn->type) }}
                                    </span>
                                </td>
                                <td>₦{{ number_format($txn->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4">No transactions</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Receipts</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentReceipts as $receipt)
                            <tr>
                                <td>{{ $receipt->payment_date->format('d M') }}</td>
                                <td>{{ $receipt->student->name ?? 'N/A' }}</td>
                                <td>₦{{ number_format($receipt->amount, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $receipt->is_verified ? 'success' : 'warning' }}">
                                        {{ $receipt->is_verified ? 'Verified' : 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4">No receipts</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection