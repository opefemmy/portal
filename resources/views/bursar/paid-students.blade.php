@extends('layouts.app')

@section('title', 'Paid Students')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-check-circle me-2"></i>Paid Students</h4>
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
        <form method="GET" action="{{ route('bursar.paid-students') }}" class="row g-3">
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
        <h5 class="mb-0">Verified Payments ({{ $paidStudents->total() ?? 0 }})</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Department</th>
                        <th>Amount</th>
                        <th>Reference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paidStudents as $payment)
                    <tr>
                        <td>{{ optional($payment->created_at)->format('d M, Y h:i A') ?? 'N/A' }}</td>
                        <td><strong>{{ $payment->student->matric_number ?? 'N/A' }}</strong></td>
                        <td>{{ $payment->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $payment->student->department->name ?? 'N/A' }}</td>
                        <td>₦{{ number_format($payment->amount, 2) }}</td>
                        <td><code>{{ $payment->reference ?? $payment->payment_ref ?? 'N/A' }}</code></td>
                        <td><span class="badge bg-success">Verified</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No verified payments yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $paidStudents->links() }}
        </div>
    </div>
</div>
@endsection