@extends('layouts.app')

@section('title', 'Financial Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-chart-bar me-2"></i>Financial Report</h4></div>

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3 bg-success text-white"><small>Total Income</small><h4>₦{{ number_format($totals['income'] ?? 0, 2) }}</h4></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-danger text-white"><small>Total Expenses</small><h4>₦{{ number_format($totals['expenses'] ?? 0, 2) }}</h4></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-info text-white"><small>This Month</small><h4>₦{{ number_format($totals['this_month'] ?? 0, 2) }}</h4></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-secondary text-white"><small>Outstanding Fees</small><h4>₦{{ number_format($totals['outstanding'] ?? 0, 2) }}</h4></div></div>
</div>

@if(!empty($monthly ?? []))
<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Monthly Trend (last 12 months)</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Month</th><th class="text-end">Income</th><th class="text-end">Expenses</th><th class="text-end">Net</th></tr></thead>
            <tbody>
                @foreach($monthly as $row)
                    <tr>
                        <td>{{ $row->label ?? $row->month ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($row->income ?? 0, 2) }}</td>
                        <td class="text-end">₦{{ number_format($row->expenses ?? 0, 2) }}</td>
                        <td class="text-end">₦{{ number_format(($row->income ?? 0) - ($row->expenses ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
