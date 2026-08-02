@extends('layouts.app')

@section('title', 'Payment Receipt')

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
        border-bottom: 2px solid #247D57;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .receipt-header h2 {
        color: #247D57;
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
        color: #247D57;
        background: #e8f5e9;
        padding: 15px;
        border-radius: 5px;
        text-align: center;
    }
    .status-paid {
        background: #28a745;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
    }
    .status-pending {
        background: #ffc107;
        color: #333;
        padding: 5px 15px;
        border-radius: 20px;
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
            <p>Payment Receipt</p>
        </div>

        <div class="receipt-details">
            <table class="table table-bordered">
                <tr>
                    <th>Payment Reference</th>
                    <td><strong>{{ $payment->payment_ref }}</strong></td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>{{ optional($payment->created_at)->format('d M Y, h:i A') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="status-{{ $payment->status }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Payer Information</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Name</th>
                    <td>{{ $payment->payer_name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $payment->payer_email }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $payment->payer_phone }}</td>
                </tr>
                <tr>
                    <th>Payer ID</th>
                    <td>{{ $payment->payer_id }}</td>
                </tr>
            </table>
        </div>

        <div class="receipt-details">
            <h5>Payment Details</h5>
            <table class="table table-bordered">
                <tr>
                    <th>Payment Purpose</th>
                    <td>{{ $payment->fee->name ?? $payment->payment_purpose }}</td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>₦{{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Portal Charge</th>
                    <td>₦{{ number_format($payment->portal_charge ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="total-amount mt-4">
            Total Paid: ₦{{ number_format($payment->total_amount ?? $payment->amount, 2) }}
        </div>

        <div class="text-center mt-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Receipt
            </button>
            <a href="{{ url('/login') }}" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Back to Home
            </a>
        </div>
    </div>
</div>
@endsection
