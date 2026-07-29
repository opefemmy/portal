@extends('layouts.app')

@section('title', 'Patient Dashboard')

@section('content')
<style>
    .portal-page {
        background: url("{{ asset('uploads/backgrounds/login-bg.png') }}") no-repeat center center fixed !important;
        background-size: cover !important;
        min-height: 100vh;
        padding: 20px 0;
    }
    .portal-card-custom {
        background: white !important;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>
<div class="portal-page">
<div class="container py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold">
                <i class="fas fa-user-circle text-danger me-2"></i>Welcome, {{ $patient->full_name }}
            </h2>
            <p class="text-muted mb-0">Patient Number: <strong>{{ $patient->patient_number }}</strong></p>
        </div>
        <div class="col-md-4 text-md-end">
            <form method="POST" action="{{ route('patient-portal.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show">
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('new_code'))
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>New Access Code:</strong> {{ session('new_code') }} - Please save this code!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-calendar-check text-danger"></i>
                    </div>
                    <h4 class="mb-0">{{ $appointments->count() }}</h4>
                    <small class="text-muted">Appointments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-credit-card text-success"></i>
                    </div>
                    <h4 class="mb-0">{{ $payments->count() }}</h4>
                    <small class="text-muted">Payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-user-md text-primary"></i>
                    </div>
                    <h4 class="mb-0">{{ $services->flatten()->count() }}</h4>
                    <small class="text-muted">Available Services</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px;">
                        <i class="fas fa-key text-warning"></i>
                    </div>
                    <h4 class="mb-0">Active</h4>
                    <small class="text-muted">Account Status</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Request Service Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Request a Service
                    </h5>
                </div>
                <div class="card-body">
                    <form id="serviceRequestForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Select Service <span class="text-danger">*</span></label>
                                <select name="service_type_id" id="service_type_id" class="form-select" required>
                                    <option value="">Select a Service</option>
                                    @foreach($services as $category => $categoryServices)
                                        <optgroup label="{{ $category }}">
                                            @foreach($categoryServices as $service)
                                                <option value="{{ $service->id }}" data-amount="{{ $service->amount }}" data-requires-appointment="{{ $service->requires_appointment ? '1' : '0' }}">
                                                    {{ $service->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Amount (₦)</label>
                                <input type="text" id="service_amount" class="form-control bg-light" readonly placeholder="Select a service to see amount">
                            </div>
                        </div>

                        <div class="row" id="appointmentFields" style="display: none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Appointment Date</label>
                                <input type="datetime-local" name="appointment_date" class="form-control" min="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Notes</label>
                                <input type="text" name="notes" class="form-control" placeholder="Any notes...">
                            </div>
                        </div>

                        <div class="alert alert-info" id="serviceInfo" style="display: none;">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="serviceInfoText">Please select a service</span>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                                <i class="fas fa-paper-plane me-2"></i>Submit Service Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- My Appointments -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>My Appointments
                    </h5>
                </div>
                <div class="card-body">
                    @if($appointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appointment)
                                        <tr>
                                            <td>{{ $appointment->appointment_date->format('d M Y, h:i A') }}</td>
                                            <td>{{ $appointment->serviceType->name ?? 'N/A' }}</td>
                                            <td>
                                                @switch($appointment->status)
                                                    @case('scheduled')
                                                        <span class="badge bg-primary">Scheduled</span>
                                                        @break
                                                    @case('completed')
                                                        <span class="badge bg-success">Completed</span>
                                                        @break
                                                    @case('cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-secondary">{{ $appointment->status }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-3">No appointments yet</p>
                    @endif
                </div>
            </div>

            <!-- Payment History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>My Payments
                    </h5>
                </div>
                <div class="card-body">
                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->created_at->format('d M Y') }}</td>
                                            <td><code>{{ $payment->payment_ref }}</code></td>
                                            <td>{{ $payment->service_name }}</td>
                                            <td>₦{{ number_format($payment->total_amount) }}</td>
                                            <td>
                                                @if($payment->status == 'completed')
                                                    <span class="badge bg-success">Paid</span>
                                                @elseif($payment->status == 'pending')
                                                    <span class="badge bg-warning">Pending</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $payment->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('patient-portal.receipt', $payment->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-3">No payments yet</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Profile Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>My Profile
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-3x text-danger"></i>
                        </div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Patient Number</small>
                        <div class="fw-bold">{{ $patient->patient_number }}</div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Access Code</small>
                        <div class="fw-bold text-danger">{{ $patient->access_code }}</div>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">Phone</small>
                        <div>{{ $patient->phone }}</div>
                    </div>

                    @if($patient->email)
                        <div class="mb-3">
                            <small class="text-muted">Email</small>
                            <div>{{ $patient->email }}</div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <small class="text-muted">Gender</small>
                        <div>{{ ucfirst($patient->gender) }}</div>
                    </div>

                    @if($patient->date_of_birth)
                        <div class="mb-3">
                            <small class="text-muted">Date of Birth</small>
                            <div>{{ $patient->date_of_birth->format('d M Y') }} ({{ $patient->age }} years)</div>
                        </div>
                    @endif

                    <div class="d-grid gap-2 mt-4">
                        <a href="{{ route('patient-portal.profile') }}" class="btn btn-outline-dark">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                        <form method="POST" action="{{ route('patient-portal.regenerate-code') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('Generate a new access code? Your old code will stop working.')">
                                <i class="fas fa-sync me-2"></i>Regenerate Access Code
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#validatePaymentModal">
                            <i class="fas fa-check-circle me-2"></i>Validate Payment
                        </button>
                        <a href="{{ route('patient-portal.profile') }}" class="btn btn-outline-info">
                            <i class="fas fa-user-cog me-2"></i>Account Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Validate Payment Modal -->
<div class="modal fade" id="validatePaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Validate Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="validatePaymentForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Payment Reference</label>
                        <input type="text" name="payment_reference" class="form-control" placeholder="Enter payment reference (e.g., HSP-XXXXXX)" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Validate
                        </button>
                    </div>
                </form>
                <div id="validationResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Service type change - Using vanilla JavaScript for reliability
    var serviceSelect = document.getElementById('service_type_id');
    var serviceAmountInput = document.getElementById('service_amount');
    var serviceInfo = document.getElementById('serviceInfo');
    var serviceInfoText = document.getElementById('serviceInfoText');
    var submitBtn = document.getElementById('submitBtn');
    var appointmentFields = document.getElementById('appointmentFields');

    if (serviceSelect) {
        serviceSelect.addEventListener('change', function() {
            var selectedValue = this.value;
            var selectedOption = this.options[this.selectedIndex];
            var selectedText = selectedOption.text;
            var amount = selectedOption.getAttribute('data-amount');
            var requiresAppointment = selectedOption.getAttribute('data-requires-appointment');

            console.log('Selected:', selectedText, 'Amount:', amount);

            if (selectedValue && amount) {
                // Format amount
                var amountNum = parseFloat(amount);
                if (!isNaN(amountNum)) {
                    serviceAmountInput.value = '₦' + amountNum.toLocaleString();
                    serviceInfo.style.display = 'block';
                    serviceInfoText.textContent = selectedText + ' - ₦' + amountNum.toLocaleString() + '. Click Submit to generate invoice and proceed to payment.';
                    submitBtn.disabled = false;
                } else {
                    serviceAmountInput.value = 'Amount: ' + amount;
                    serviceInfo.style.display = 'block';
                    serviceInfoText.textContent = selectedText + ' - ' + amount + '. Click Submit to generate invoice.';
                    submitBtn.disabled = false;
                }
            } else {
                serviceAmountInput.value = '';
                serviceInfo.style.display = 'none';
                submitBtn.disabled = true;
            }

            // Show/hide appointment fields
            if (appointmentFields) {
                if (requiresAppointment === '1') {
                    appointmentFields.style.display = 'flex';
                } else {
                    appointmentFields.style.display = 'none';
                }
            }
        });
    }

    // Submit service request
    var serviceForm = document.getElementById('serviceRequestForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            var serviceName = selectedOption.text.trim();
            var amount = selectedOption.getAttribute('data-amount');

            // Confirm before submitting
            var confirmMsg = 'You are about to request: ' + serviceName;
            if (amount) {
                confirmMsg += '\nAmount: ₦' + parseFloat(amount).toLocaleString();
            }
            confirmMsg += '\n\nClick OK to generate invoice and proceed to payment.';

            if (!confirm(confirmMsg)) {
                return;
            }

            // Get form data
            var formData = new FormData(serviceForm);

            fetch('{{ route("patient-portal.request-service") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('Service request submitted! Request Code: ' + data.request_code);
                location.reload();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });
    }

    // Validate payment
    var validateForm = document.getElementById('validatePaymentForm');
    var validationResult = document.getElementById('validationResult');
    if (validateForm && validationResult) {
        validateForm.addEventListener('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(validateForm);

            fetch('{{ route("patient-portal.validate-payment-portal") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var p = data.payment;
                    validationResult.innerHTML = '<div class="alert alert-success"><strong>Payment Verified!</strong><br>Reference: ' + p.reference + '<br>Service: ' + p.service_name + '<br>Amount: ₦' + p.total_amount + '<br>Status: ' + p.status + '</div>';
                } else {
                    validationResult.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
                }
            })
            .catch(error => {
                validationResult.innerHTML = '<div class="alert alert-danger">Error validating payment</div>';
            });
        });
    }
});
</script>
</div>
@endpush
@endsection
