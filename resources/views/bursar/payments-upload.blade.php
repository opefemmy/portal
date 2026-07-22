@extends('layouts.app')

@section('title', 'Upload External Payments')

@section('content')
<div class="page-header">
    <h4>Upload External Payments</h4>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Upload Payment File</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('bursar.payments.upload.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Select Fee Type *</label>
                        <select name="fee_id" class="form-select" required>
                            <option value="">Select Fee</option>
                            @foreach($fees as $fee)
                            <option value="{{ $fee->id }}">{{ $fee->name }} - ₦{{ number_format($fee->amount) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Payment File (CSV) *</label>
                        <input type="file" name="payment_file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                        <small class="text-muted">Upload a CSV file with columns: payment_ref, amount, payment_date, matric_number (or application_number or email or phone)</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Upload Payments
                    </button>
                </form>
            </div>
        </div>

        @if(session('results'))
        <div class="card mt-3">
            <div class="card-header">
                <h5>Upload Results</h5>
            </div>
            <div class="card-body">
                <p><strong>Created:</strong> {{ session('results')['created'] }}</p>
                <p><strong>Updated:</strong> {{ session('results')['updated'] }}</p>
                @if(!empty(session('results')['errors']))
                <p><strong>Errors:</strong></p>
                <ul class="text-danger">
                    @foreach(session('results')['errors'] as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>CSV Format Guide</h5>
            </div>
            <div class="card-body">
                <h6>Required Columns:</h6>
                <ul>
                    <li><code>payment_ref</code> - Payment reference/transaction ID</li>
                    <li><code>amount</code> - Amount paid</li>
                    <li><code>payment_date</code> - Date of payment (YYYY-MM-DD)</li>
                </ul>

                <h6>Student Identification (at least one required):</h6>
                <ul>
                    <li><code>matric_number</code> - Student matric number</li>
                    <li><code>application_number</code> - Applicant application number</li>
                    <li><code>email</code> - Student/applicant email</li>
                    <li><code>phone</code> - Phone number</li>
                </ul>

                <h6>Example CSV:</h6>
                <pre class="bg-light p-2">payment_ref,amount,payment_date,matric_number
TXN001,5000,2024-01-15,2024/001
TXN002,5000,2024-01-16,APP-ABC123</pre>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Add file size validation
    document.querySelector('input[name="payment_file"]').addEventListener('change', function() {
        const fileSize = this.files[0].size / 1024 / 1024; // in MB
        if (fileSize > 5) {
            alert('File size exceeds 5MB limit');
            this.value = '';
        }
    });
</script>
@endpush
