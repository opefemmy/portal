@extends('layouts.app')

@section('title', 'Hospital Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-hospital me-2"></i>Hospital Report</h4></div>

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3 bg-primary text-white"><small>Total Patients</small><h3>{{ $totals['patients'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-success text-white"><small>Total Appointments</small><h3>{{ $totals['appointments'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-info text-white"><small>Admissions</small><h3>{{ $totals['admissions'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-warning text-dark"><small>Pending Lab</small><h3>{{ $totals['pending_lab'] ?? 0 }}</h3></div></div>
</div>

@if(!empty($byDepartment ?? []))
<div class="card mb-3">
    <div class="card-header bg-info text-white"><h5 class="mb-0">By Department</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Department</th><th class="text-end">Patients</th><th class="text-end">Appointments</th></tr></thead>
            <tbody>
                @foreach($byDepartment as $row)
                    <tr><td>{{ $row->name ?? '—' }}</td><td class="text-end">{{ $row->patients ?? 0 }}</td><td class="text-end">{{ $row->appointments ?? 0 }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(!empty($lowStock ?? []))
<div class="card">
    <div class="card-header bg-danger text-white"><h5 class="mb-0">Low Stock Drugs</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Drug</th><th>Category</th><th class="text-end">Stock</th><th class="text-end">Reorder Level</th></tr></thead>
            <tbody>
                @foreach($lowStock as $d)
                    <tr>
                        <td>{{ $d->name ?? '—' }}</td>
                        <td>{{ $d->category ?? '—' }}</td>
                        <td class="text-end">{{ $d->current_stock ?? 0 }}</td>
                        <td class="text-end">{{ $d->reorder_level ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
