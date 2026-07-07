@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Executive Dashboard</h4>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Students</h5>
                    <h2>{{ number_format($studentStats['total']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Staff</h5>
                    <h2>{{ number_format($staffStats['total']) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Today Revenue</h5>
                    <h2>₦{{ number_format($financialStats['today_revenue'], 0) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Monthly Revenue</h5>
                    <h2>₦{{ number_format($financialStats['monthly_revenue'], 0) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Students by Department</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDepartments as $dept)
                            <tr>
                                <td>{{ $dept->name }}</td>
                                <td>{{ $dept->count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Hospital Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h3>{{ $hospitalStats['today_appointments'] }}</h3>
                            <p>Today's Appointments</p>
                        </div>
                        <div class="col-md-4">
                            <h3>{{ $hospitalStats['admitted_patients'] }}</h3>
                            <p>Admitted Patients</p>
                        </div>
                        <div class="col-md-4">
                            <h3>₦{{ number_format($financialStats['outstanding'], 0) }}</h3>
                            <p>Outstanding Balance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Receipts</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentReceipts as $receipt)
                            <tr>
                                <td>{{ $receipt->created_at->format('d M Y') }}</td>
                                <td>{{ $receipt->student->name ?? 'N/A' }}</td>
                                <td>₦{{ number_format($receipt->amount, 2) }}</td>
                                <td>{{ ucfirst($receipt->payment_method) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4">No recent receipts</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection