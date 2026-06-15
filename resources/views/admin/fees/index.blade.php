@extends('layouts.app')

@section('title', 'Fees')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Fees Configuration</h4>
    <a href="{{ route('admin.fees.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Fee
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Payment Type</th>
                        <th>Amount</th>
                        <th>School</th>
                        <th>Department</th>
                        <th>Programme</th>
                        <th>Level</th>
                        <th>Session</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fees as $fee)
                    <tr>
                        <td>{{ $fee->name }}</td>
                        <td>{{ $fee->payment_type ?? 'N/A' }}</td>
                        <td>₦{{ number_format($fee->amount, 2) }}</td>
                        <td>{{ $fee->school->name ?? 'All' }}</td>
                        <td>{{ $fee->department->name ?? 'All' }}</td>
                        <td>{{ $fee->programme->name ?? 'All' }}</td>
                        <td>{{ $fee->levelDisplay }}</td>
                        <td>{{ $fee->session->name ?? 'N/A' }}</td>
                        <td>{{ $fee->due_date?->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.fees.edit', $fee) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this fee">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.fees.destroy', $fee) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this fee?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this fee">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4">No fees configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection