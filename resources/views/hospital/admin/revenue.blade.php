@extends('layouts.app')

@section('title', 'Revenue')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-coins me-2"></i>Revenue</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item active">Revenue</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto">
            <form method="GET" class="d-inline">
                <select name="days" onchange="this.form.submit()" class="form-select d-inline w-auto">
                    @foreach([7,14,30,60] as $d)
                        <option value="{{ $d }}" @selected((int)request('days', 14) === $d)>Last {{ $d }} days</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4"><div class="card stat-card"><div class="card-body">
        <p class="text-muted mb-1">This month total</p>
        <h3>₦{{ number_format($monthTotal, 0) }}</h3>
    </div></div></div>
    <div class="col-md-8"><div class="card"><div class="card-body">
        <h5 class="card-title"><i class="fas fa-calendar-day me-2"></i>Daily totals</h5>
        <table class="table table-sm">
            <thead><tr><th>Day</th><th class="text-end">Count</th><th class="text-end">Total</th></tr></thead>
            <tbody>
                @forelse($daily as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->day)->format('D d M') }}</td>
                        <td class="text-end">{{ $row->cnt }}</td>
                        <td class="text-end">₦{{ number_format($row->total, 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted text-center">No completed payments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div></div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="fas fa-stethoscope me-2"></i>By service</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Service</th><th class="text-end">Count</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($byService as $row)
                            <tr>
                                <td>{{ $row->service_name ?? '—' }}</td>
                                <td class="text-end">{{ $row->cnt }}</td>
                                <td class="text-end">₦{{ number_format($row->total, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h5 class="card-title"><i class="fas fa-credit-card me-2"></i>By payment method</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Method</th><th class="text-end">Count</th><th class="text-end">Total</th></tr></thead>
                    <tbody>
                        @forelse($byMethod as $row)
                            <tr>
                                <td>{{ ucfirst(str_replace('_',' ', $row->payment_method ?? '—')) }}</td>
                                <td class="text-end">{{ $row->cnt }}</td>
                                <td class="text-end">₦{{ number_format($row->total, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection