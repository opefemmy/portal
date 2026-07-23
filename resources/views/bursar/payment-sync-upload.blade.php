@extends('layouts.app')

@section('title', 'Upload Payments')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">Upload Payments</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('bursar.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('bursar.payments.sync.index') }}">Payment Synchronization</a></li>
                <li class="breadcrumb-item active">Upload</li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Upload Payment Records</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('bursar.payments.sync.preview') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label for="file" class="form-label">Select Excel or CSV File</label>
                        <input type="file"
                               class="form-control @error('file') is-invalid @enderror"
                               id="file"
                               name="file"
                               accept=".xlsx,.csv"
                               required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Maximum file size: 5MB. Supported formats: Excel (.xlsx), CSV (.csv)
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Required Columns:</h6>
                        <ul class="mb-0">
                            <li><strong>Transaction ID</strong> - Unique payment reference</li>
                            <li><strong>Applicant Name</strong> - Name of the applicant</li>
                            <li><strong>Email</strong> - Applicant's email address</li>
                            <li><strong>Amount</strong> - Payment amount</li>
                            <li><strong>Payment Date</strong> - Date of payment</li>
                            <li><strong>Payment Status</strong> - Status (completed, pending, failed)</li>
                            <li><strong>Payment Channel</strong> - Payment method (card, bank, USSD, etc.)</li>
                        </ul>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-eye me-2"></i> Preview Data
                        </button>
                        <a href="{{ route('bursar.payments.sync.index') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <a href="{{ route('bursar.payments.sync.template') }}" class="btn btn-outline-info ms-auto">
                            <i class="fas fa-download me-2"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
