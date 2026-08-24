@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-money-bill-wave me-2"></i>Payroll</h4>
    <a href="{{ route('finance.payroll.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Payroll</a>
</div>
@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Payroll #</th><th>Period</th><th>Department</th><th class="text-end">Employees</th><th class="text-end">Gross</th><th class="text-end">Net</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($payrolls ?? [] as $p)
                <tr>
                    <td><strong>{{ $p->payroll_number ?? '#'.$p->id }}</strong></td>
                    <td>{{ optional($p->period_start)->format('M d') ?? '—' }} → {{ optional($p->period_end)->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $p->department->name ?? '—' }}</td>
                    <td class="text-end">{{ $p->employee_count ?? '—' }}</td>
                    <td class="text-end">₦{{ number_format($p->gross_amount ?? 0, 2) }}</td>
                    <td class="text-end">₦{{ number_format($p->net_amount ?? 0, 2) }}</td>
                    <td><span class="badge bg-{{ ['paid'=>'success','approved'=>'info','pending'=>'warning','draft'=>'secondary'][$p->status] ?? 'secondary' }}">{{ ucfirst($p->status ?? 'draft') }}</span></td>
                    <td>
                        <a href="{{ route('finance.payroll.show', $p) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No payroll runs yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="mt-3">{{ ($payrolls ?? null)?->appends(request()->query())->links() }}</div>
</div></div>
@endsection
