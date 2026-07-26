@extends('layouts.app')

@section('title', 'Hospital Payment Receipt')

@section('content')
<style>
    .receipt {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    .receipt-header {
        text-align: center;
        border-bottom: 2px solid #dc3545;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .receipt-header h2 {
        color: #dc3545;
        margin-bottom: 5px;
    }
    .receipt-details {
        margin: 20px 0;
    }
    .receipt-details table {
        width: 100%;
    }
    .receipt-details th {
        text-align: left;
        padding: 10px;
        background: #f8f9fa;
        width: 40%;
    }
    .receipt-details td {
        padding: 10px;
    }
    .total-amount {
        font-size: 1.5rem;
        font-weight: bold;
        color: #dc3545;
        background: #fce4e4;
        padding: 15px;
        border-radius: 5px;
        text-align: center;
    }
    .status-badge {
        padding: 5px 15px;
        border-radius: 20px;
    }
    .status-completed {
        background: #28a745;
        color: white;
    }
    .status-pending {
        background: #ffc107;
        color: #333;
    }
    @media print {
        body { background: white; }
        .receipt { box-shadow: none; }
        .no-print { display: none; }
    }
</style>

<div class="container py-4">
    <div class="receipt">
        <div class="receipt-header">
            <h2>{{ config('app.name', 'Institution Management Portal') }}</h2>
            <p>Hospital Services Payment Receipt</p>
        </div>

        <div class="receipt-details">
            <table class="table table-bordered">
                <tr>
                    <th>Payment Reference</th>
                    <td><strong>{{ $payment->payment_ref }}</strong></td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="status-badge status-{{ $payment->status }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Patient Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <td>{{ $payment->patient_name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $payment->patient_email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $payment->patient_phone }}</td>
                </tr>
                <tr>
                    <th>Gender</th>
                    <td>{{ $payment->patient_gender ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Age</th>
                    <td>{{ $payment->patient_age ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Service Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Service</th>
                    <td>{{ $payment->service_name }}</td>
                </tr>
                @if($payment->appointment_date)
                <tr>
                    <th>Appointment Date</th>
                    <td>{{ \Carbon\Carbon::parse($payment->appointment_date)->format('d M Y, h:i A') }}</td>
                </tr>
                @endif
                @if($payment->doctor_name)
                <tr>
                    <th>Doctor</th>
                    <td>{{ $payment->doctor_name }}</td>
                </tr>
                @endif
                <tr>
                    <th>Payment Method</th>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                </tr>
                <tr>
                    <th>Service Amount</th>
                    <td>₦{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Portal Charge</th>
                    <td>₦{{ number_format($payment->portal_charge ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="total-amount mt-4">
            Total Paid: ₦{{ number_format($payment->total_amount, 2) }}
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-danger">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <a href="{{ url('/login') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>
@endsection
