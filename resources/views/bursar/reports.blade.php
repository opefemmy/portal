@extends('layouts.app')

@section('title', 'Bursar Reports')

@section('content')
<div class="page-header">
    <h4>Bursar Reports</h4>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-file-invoice fa-3x text-primary mb-3"></i>
                <h5>Payment Reports</h5>
                <p class="text-muted">View all payment transactions</p>
                <a href="{{ route('bursar.payments') }}" class="btn btn-primary">
                    <i class="fas fa-eye me-2"></i>View Payments
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                <h5>Revenue Reports</h5>
                <p class="text-muted">View revenue by session, semester</p>
                <button class="btn btn-success" onclick="alert('Coming soon!')">
                    <i class="fas fa-chart-line me-2"></i>View Revenue
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-info mb-3"></i>
                <h5>Student Payment Status</h5>
                <p class="text-muted">View students who have paid</p>
                <button class="btn btn-info" onclick="alert('Coming soon!')">
                    <i class="fas fa-list me-2"></i>View Status
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <td>Total Payments Today:</td>
                        <td><strong>₦0.00</strong></td>
                    </tr>
                    <tr>
                        <td>Total Payments This Month:</td>
                        <td><strong>₦0.00</strong></td>
                    </tr>
                    <tr>
                        <td>Pending Payments:</td>
                        <td><strong>0</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Recent Payments</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">No recent payments found.</p>
            </div>
        </div>
    </div>
</div>
@endsection