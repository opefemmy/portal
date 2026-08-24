@extends('layouts.app')

@section('title', 'Students Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-user-graduate me-2"></i>Students Report</h4></div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="row mb-3">
    <div class="col-md-3"><div class="card text-center p-3 bg-primary text-white"><small>Total</small><h3>{{ $totals['total'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-success text-white"><small>Active</small><h3>{{ $totals['active'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-info text-white"><small>Male</small><h3>{{ $totals['male'] ?? 0 }}</h3></div></div>
    <div class="col-md-3"><div class="card text-center p-3 bg-warning text-dark"><small>Female</small><h3>{{ $totals['female'] ?? 0 }}</h3></div></div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">By Department</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Department</th><th class="text-end">Total</th><th class="text-end">Active</th><th class="text-end">Male</th><th class="text-end">Female</th></tr></thead>
            <tbody>
                @forelse($byDepartment ?? [] as $row)
                    <tr><td>{{ $row->name ?? '—' }}</td><td class="text-end">{{ $row->total ?? 0 }}</td><td class="text-end">{{ $row->active ?? 0 }}</td><td class="text-end">{{ $row->male ?? 0 }}</td><td class="text-end">{{ $row->female ?? 0 }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!empty($byLevel ?? []))
<div class="card mt-3">
    <div class="card-header bg-info text-white"><h5 class="mb-0">By Level</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Level</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @foreach($byLevel as $row)
                    <tr><td>{{ $row->level ?? '—' }}</td><td class="text-end">{{ $row->total ?? 0 }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
