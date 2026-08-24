@extends('layouts.app')

@section('title', 'Staff Report')

@section('content')
<div class="page-header"><h4><i class="fas fa-user-tie me-2"></i>Staff Report</h4></div>

<div class="row mb-3">
    <div class="col-md-4"><div class="card text-center p-3 bg-primary text-white"><small>Total Staff</small><h3>{{ $totals['total'] ?? 0 }}</h3></div></div>
    <div class="col-md-4"><div class="card text-center p-3 bg-success text-white"><small>Active</small><h3>{{ $totals['active'] ?? 0 }}</h3></div></div>
    <div class="col-md-4"><div class="card text-center p-3 bg-secondary text-white"><small>Inactive</small><h3>{{ $totals['inactive'] ?? 0 }}</h3></div></div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white"><h5 class="mb-0">By Role</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Role</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @forelse($byRole ?? [] as $row)
                    <tr><td>{{ $row->name ?? '—' }}</td><td class="text-end">{{ $row->total ?? 0 }}</td></tr>
                @empty
                    <tr><td colspan="2" class="text-center py-4 text-muted">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!empty($byDepartment ?? []))
<div class="card mt-3">
    <div class="card-header bg-info text-white"><h5 class="mb-0">By Department</h5></div>
    <div class="card-body">
        <table class="table datatable">
            <thead class="table-light"><tr><th>Department</th><th class="text-end">Count</th></tr></thead>
            <tbody>
                @foreach($byDepartment as $row)
                    <tr><td>{{ $row->name ?? '—' }}</td><td class="text-end">{{ $row->total ?? 0 }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
