@extends('layouts.app')

@section('title', 'Debtors')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-exclamation-circle me-2"></i>Debtors List</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('bursar.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
        <a href="{{ route('bursar.reports') }}" class="btn btn-outline-primary">
            <i class="fas fa-chart-bar me-2"></i>Reports
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('bursar.debtors') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Matric or Name" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Students With Outstanding Fees ({{ $debtors->total() ?? 0 }})</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debtors as $debtor)
                    <tr>
                        <td><strong>{{ $debtor->matric_number ?? 'N/A' }}</strong></td>
                        <td>{{ $debtor->user->name ?? 'N/A' }}</td>
                        <td>{{ $debtor->user->email ?? 'N/A' }}</td>
                        <td>{{ $debtor->department->name ?? 'N/A' }}</td>
                        <td>{{ $debtor->programme->name ?? 'N/A' }}</td>
                        <td>{{ $debtor->level_display ?? $debtor->level ?? 'N/A' }}</td>
                        <td><span class="badge bg-danger">Not Paid</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-success py-4">
                            <i class="fas fa-check-circle me-2"></i>All students have paid!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $debtors->links() }}
        </div>
    </div>
</div>
@endsection