@extends('layouts.app')

@section('title', 'Bursar Dashboard')

@section('content')
<div class="page-header">
    <h4>Bursar Dashboard</h4>
</div>

{{-- Payment Statistics Cards --}}
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Total Expected</h6>
                <h2>₦{{ number_format($paymentStats['total_expected'] ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <h6 class="text-muted">Total Paid</h6>
                <h2>₦{{ number_format($paymentStats['total_paid'] ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Total Pending</h6>
                <h2>₦{{ number_format($paymentStats['total_pending'] ?? 0, 2) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Debtors Count</h6>
                <h2>{{ $paymentStats['debtors_count'] ?? 0 }}</h2>
            </div>
        </div>
    </div>
</div>

{{-- Filter by School --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('bursar.dashboard') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filter by School</label>
                <select name="school_id" class="form-select">
                    <option value="">All Schools</option>
                    @foreach($schools as $school)
                        <option value="{{ $school->id }}" {{ request('school_id') == $school->id ? 'selected' : '' }}>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

{{-- Tabs for Debtors and Paid Students --}}
<ul class="nav nav-tabs mb-4" id="paymentTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="debtors-tab" data-bs-toggle="tab" data-bs-target="#debtors" type="button">
            <i class="fas fa-exclamation-circle me-2"></i>Debtors List
            <span class="badge bg-danger ms-2">{{ $paymentStats['debtors_count'] ?? 0 }}</span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="paid-tab" data-bs-toggle="tab" data-bs-target="#paid" type="button">
            <i class="fas fa-check-circle me-2"></i>Paid Students
            <span class="badge bg-success ms-2">{{ $paymentStats['paid_count'] ?? 0 }}</span>
        </button>
    </li>
</ul>

<div class="tab-content" id="paymentTabsContent">
    {{-- Debtors List --}}
    <div class="tab-pane fade show active" id="debtors" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Debtors List - {{ $currentSession->name ?? 'Current Session' }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Matric Number</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Programme</th>
                                <th>Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($debtors as $debtor)
                            <tr>
                                <td>{{ $debtor->matric_number ?? 'N/A' }}</td>
                                <td>{{ $debtor->user->name ?? 'N/A' }}</td>
                                <td>{{ $debtor->department->name ?? 'N/A' }}</td>
                                <td>{{ $debtor->programme->name ?? 'N/A' }}</td>
                                <td>{{ $debtor->level_display ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-danger">Not Paid</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-success">
                                    <i class="fas fa-check-circle me-2"></i>All students have paid!
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $debtors->links() }}
            </div>
        </div>
    </div>

    {{-- Paid Students --}}
    <div class="tab-pane fade" id="paid" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Paid Students - {{ $currentSession->name ?? 'Current Session' }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover datatable">
                        <thead>
                            <tr>
                                <th>Matric Number</th>
                                <th>Full Name</th>
                                <th>Department</th>
                                <th>Programme</th>
                                <th>Amount Paid</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paidStudents as $payment)
                            <tr>
                                <td>{{ $payment->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $payment->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $payment->student->department->name ?? 'N/A' }}</td>
                                <td>{{ $payment->student->programme->name ?? 'N/A' }}</td>
                                <td>₦{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->created_at->format('d M, Y') }}</td>
                                <td>
                                    <span class="badge bg-success">Paid</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No payments recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $paidStudents->links() }}
            </div>
        </div>
    </div>
</div>
@endsection