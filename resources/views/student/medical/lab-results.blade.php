@extends('layouts.app')

@section('title', 'Lab Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Lab Results</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Medical Portal
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Date Requested</th>
                    <th>Test Type</th>
                    <th>Doctor</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($labRequests as $labRequest)
                <tr>
                    <td>{{ $labRequest->requested_at->format('d M Y') }}</td>
                    <td>{{ $labRequest->test_type }}</td>
                    <td>Dr. {{ $labRequest->doctor->last_name ?? 'N/A' }}</td>
                    <td>
                        @switch($labRequest->status)
                            @case('pending')
                                <span class="badge bg-warning">Pending</span>
                                @break
                            @case('in_progress')
                                <span class="badge bg-info">In Progress</span>
                                @break
                            @case('completed')
                                <span class="badge bg-success">Completed</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-danger">Cancelled</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ $labRequest->status }}</span>
                        @endswitch
                    </td>
                    <td>
                        @if($labRequest->status === 'completed')
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#labResultModal{{ $labRequest->id }}">
                            <i class="fas fa-eye"></i> View Results
                        </button>
                        @else
                        <span class="text-muted">Not available</span>
                        @endif
                    </td>
                </tr>

                <!-- Lab Result Detail Modal -->
                @if($labRequest->status === 'completed')
                <div class="modal fade" id="labResultModal{{ $labRequest->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Lab Results - {{ $labRequest->test_type }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <p><strong>Requested by:</strong> Dr. {{ $labRequest->doctor->first_name ?? '' }} {{ $labRequest->doctor->last_name ?? '' }}</p>
                                    <p><strong>Date:</strong> {{ $labRequest->requested_at->format('d M Y') }}</p>
                                    <p><strong>Completed:</strong> {{ $labRequest->completed_at->format('d M Y h:i A') }}</p>
                                </div>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Test Parameter</th>
                                            <th>Result</th>
                                            <th>Reference Range</th>
                                            <th>Unit</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($labRequest->results as $result)
                                        <tr>
                                            <td>{{ $result->parameter }}</td>
                                            <td><strong>{{ $result->value }}</strong></td>
                                            <td>{{ $result->reference_range ?? 'N/A' }}</td>
                                            <td>{{ $result->unit ?? 'N/A' }}</td>
                                            <td>
                                                @if($result->is_abnormal)
                                                <span class="badge bg-danger">Abnormal</span>
                                                @else
                                                <span class="badge bg-success">Normal</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No results available</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                @if($labRequest->notes)
                                <div class="mt-3">
                                    <p><strong>Lab Notes:</strong></p>
                                    <p>{{ $labRequest->notes }}</p>
                                </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="window.print()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                @empty
                <tr>
                    <td colspan="5" class="text-center">No lab requests found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $labRequests->links() }}
    </div>
</div>
@endsection