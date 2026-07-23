@extends('layouts.app')

@section('title', 'Preview Import')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Preview Payment Import</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('bursar.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('bursar.payments.sync.index') }}">Payment Synchronization</a></li>
                <li class="breadcrumb-item active">Preview</li>
            </ul>
        </div>
    </div>
</div>

@if(!empty($errors))
<div class="alert alert-danger">
    <h6><i class="fas fa-exclamation-triangle me-2"></i>Validation Errors ({{ count($errors) }})</h6>
    <ul class="mb-0">
        @foreach($errors as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="card-title">
                            <i class="fas fa-file-excel me-2"></i>
                            File: {{ $filename }}
                        </h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-primary">{{ count($rows) }} Records</span>
                        @if(!empty($errors))
                            <span class="badge bg-danger">{{ count($errors) }} Errors</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered table-hover" id="previewTable">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>#</th>
                                <th>Transaction ID</th>
                                <th>Applicant Name</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th>Channel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                            <tr>
                                <td>{{ $row['row'] }}</td>
                                <td><code>{{ $row['transaction_id'] }}</code></td>
                                <td>{{ $row['applicant_name'] }}</td>
                                <td>{{ $row['email'] }}</td>
                                <td>₦{{ number_format($row['amount'], 2) }}</td>
                                <td>{{ $row['payment_date'] }}</td>
                                <td>
                                    @if(strtolower($row['payment_status']) === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif(strtolower($row['payment_status']) === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $row['payment_channel'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('bursar.payments.sync.import') }}">
                    @csrf

                    <div class="form-check mb-3">
                        <input class="form-check-input"
                               type="checkbox"
                               id="skip_duplicates"
                               name="skip_duplicates"
                               value="1"
                               checked>
                        <label class="form-check-label" for="skip_duplicates">
                            Skip duplicate Transaction IDs (recommended)
                        </label>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" @if(count($rows) === 0) disabled @endif>
                            <i class="fas fa-import me-2"></i> Import {{ count($rows) }} Records
                        </button>
                        <a href="{{ route('bursar.payments.sync.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <a href="{{ route('bursar.payments.sync.upload') }}" class="btn btn-outline-primary">
                            <i class="fas fa-upload me-2"></i> Upload Different File
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
