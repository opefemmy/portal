@extends('layouts.app')

@section('title', 'Payroll #'.$payroll->payroll_number ?? $payroll->id)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-money-bill-wave me-2"></i>Payroll #{{ $payroll->payroll_number ?? $payroll->id }}</h4>
    <div>
        @if(($payroll->status ?? 'draft') === 'draft')
            <form method="POST" action="{{ route('finance.payroll.approve', $payroll) }}" class="d-inline">
                @csrf
                <button class="btn btn-success"><i class="fas fa-check me-2"></i>Approve</button>
            </form>
        @elseif(($payroll->status ?? '') === 'approved')
            <form method="POST" action="{{ route('finance.payroll.pay', $payroll) }}" class="d-inline">
                @csrf
                <button class="btn btn-primary"><i class="fas fa-credit-card me-2"></i>Mark Paid</button>
            </form>
        @endif
        <a href="{{ route('finance.payroll.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3"><small>Period</small><h6>{{ optional($payroll->period_start)->format('M d') }} → {{ optional($payroll->period_end)->format('M d, Y') }}</h6></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small>Gross</small><h5>₦{{ number_format($payroll->gross_amount ?? 0, 2) }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small>Net</small><h5 class="text-success">₦{{ number_format($payroll->net_amount ?? 0, 2) }}</h5></div></div>
    <div class="col-md-3"><div class="card text-center p-3"><small>Status</small><h5><span class="badge bg-{{ ['paid'=>'success','approved'=>'info','pending'=>'warning','draft'=>'secondary'][$payroll->status] ?? 'secondary' }}">{{ ucfirst($payroll->status ?? 'draft') }}</span></h5></div></div>
</div>

@if(($items ?? collect())->count())
<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">Line Items</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Employee</th><th>Department</th><th class="text-end">Basic</th><th class="text-end">Allowances</th><th class="text-end">Deductions</th><th class="text-end">Net</th></tr></thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td>{{ $item->employee->user->name ?? $item->employee_name ?? '—' }}</td>
                        <td>{{ $item->employee->department->name ?? '—' }}</td>
                        <td class="text-end">₦{{ number_format($item->basic ?? 0, 2) }}</td>
                        <td class="text-end">₦{{ number_format($item->allowances ?? 0, 2) }}</td>
                        <td class="text-end">₦{{ number_format($item->deductions ?? 0, 2) }}</td>
                        <td class="text-end">₦{{ number_format($item->net ?? 0, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No line items yet. Add employees from the payroll controller's admin screen.</div>
@endif
@endsection
