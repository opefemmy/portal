@extends('layouts.app')

@section('title', 'Hostel Allocations')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-bed me-2"></i>Hostel Allocations</h4>
    <a href="{{ route('admin.hostels.allocations.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Allocate Room</a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach(['active','checked_out','cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filter</button></div>
</form>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead class="table-light">
                    <tr><th>Student</th><th>Hostel</th><th>Room</th><th>Session</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($allocations as $a)
                        <tr>
                            <td><strong>{{ $a->student->user->name ?? $a->student->matric_number ?? '—' }}</strong><br><small class="text-muted">{{ $a->student->matric_number ?? '' }}</small></td>
                            <td>{{ $a->hostel->name ?? '—' }}</td>
                            <td>{{ $a->room->room_number ?? '—' }}</td>
                            <td>{{ $a->session->name ?? '—' }}</td>
                            <td>{{ optional($a->check_in_date)->format('M d, Y') }}</td>
                            <td>{{ optional($a->check_out_date)->format('M d, Y') ?? '—' }}</td>
                            <td><span class="badge bg-{{ $a->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst(str_replace('_',' ',$a->status ?? '')) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No allocations yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $allocations->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
