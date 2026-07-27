@extends('layouts.app')

@section('title', 'Login')

@php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;

$institutionName = 'Ekiti State College of Technology';
$institutionShortName = 'EKSCOTECH';
$institutionLogo = null;
$institutionTagline = 'Staff, Student & Admin Login';
$logoExists = false;
$publicLogoExists = file_exists(public_path('images/logo.png'));

if (Schema::hasTable('system_settings')) {
    try {
        // Cache system settings for 60 minutes to improve performance
        $institutionName = Cache::remember('institution_name', 60, fn() => SystemSetting::get('institution_name', 'Ekiti State College of Technology'));
        $institutionShortName = Cache::remember('institution_short_name', 60, fn() => SystemSetting::get('institution_short_name', 'EKSCOTECH'));
        $institutionLogo = Cache::remember('institution_logo', 60, fn() => SystemSetting::get('institution_logo'));
        $institutionTagline = Cache::remember('institution_tagline', 60, fn() => SystemSetting::get('institution_tagline', 'Staff, Student & Admin Login'));
        $logoPath = $institutionLogo ? storage_path('app/public/' . $institutionLogo) : null;
        $logoExists = $institutionLogo && file_exists($logoPath);
    } catch (\Exception $e) {
        // Use defaults
    }
}
@endphp

@section('content')
<style>
    .login-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none !important;
    }
    .login-page::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url("{{ asset('uploads/backgrounds/login-bg.png') }}") !important;
        background-size: cover !important;
        background-position: center !important;
        background-repeat: no-repeat !important;
        z-index: -1;
    }

    .login-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
    }

    .login-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        padding: 30px;
        text-align: center;
    }

    .login-header h3 {
        color: white;
        margin: 0;
        font-weight: 600;
    }

    .login-header p {
        color: rgba(255,255,255,0.8);
        margin: 5px 0 0;
    }

    .role-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 5px;
        margin-top: 10px;
    }

    .role-badges span {
        font-size: 10px;
        padding: 2px 8px;
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
    }

    .login-body {
        padding: 40px;
    }

    .form-control {
        border-radius: 8px;
        padding: 12px 15px;
        border: 2px solid #e9ecef;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(36, 125, 87, 0.1);
    }

    .btn-login {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border: none;
        border-radius: 8px;
        padding: 12px;
        font-weight: 600;
        color: white;
        width: 100%;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(36, 125, 87, 0.4);
    }

    .input-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .input-group input {
        padding-left: 40px;
    }

    .register-link {
        text-align: center;
        margin-top: 20px;
    }

    .register-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    .institution-logo {
        font-size: 2rem;
        color: white;
        margin-bottom: 10px;
    }

    .login-header h3 {
        font-size: 2rem !important;
        font-weight: 700 !important;
    }

    .login-header p {
        font-size: 1.1rem !important;
        font-weight: 500 !important;
    }

    /* Quick Action Buttons */
    .quick-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid rgba(255,255,255,0.2);
    }

    .quick-action-btn {
        flex: 1;
        padding: 15px 10px;
        border-radius: 10px;
        text-align: center;
        color: white;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .quick-action-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        color: white;
    }

    .quick-action-btn i {
        font-size: 1.8rem;
    }

    .quick-action-btn span {
        font-size: 0.85rem;
        font-weight: 500;
    }

    .btn-online-payment {
        background: linear-gradient(135deg, #247D57, #1E6A4A);
    }

    .btn-online-payment:hover {
        background: linear-gradient(135deg, #1E6A4A, #247D57);
    }

    .btn-hospital {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }

    .btn-hospital:hover {
        background: linear-gradient(135deg, #c82333, #dc3545);
    }
</style>

<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            @if($publicLogoExists)
                <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
            @elseif($institutionLogo && $logoExists)
                <img src="{{ asset('storage/' . $institutionLogo) }}" alt="Logo" style="max-height: 60px; margin-bottom: 10px;">
            @else
                <i class="fas fa-university institution-logo"></i>
            @endif
            <h3>{{ $institutionShortName }} Portal</h3>
            <p>{{ $institutionTagline }}</p>
            <div class="role-badges">
                <span>Admin</span>
                <span>Lecturer</span>
                <span>Student</span>
                <span>Bursar</span>
                <span>Librarian</span>
                <span>Hospital</span>
            </div>
        </div>

        <div class="login-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Matric Number / Email</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-user-graduate"></i></span>
                        <input type="text" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}"
                               placeholder="Enter matric number or email" required autofocus>
                    </div>
                    <small class="text-muted">Students: Use your matriculation number (e.g., ND/2024/001)</small>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password"
                               placeholder="Enter your password" required>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>

                <div class="mb-4 text-end">
                    <a href="{{ route('password.forgot') }}" style="color: var(--primary); font-size: 0.9rem;">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                </button>
            </form>

            <!-- Quick Action Buttons -->
            <div class="quick-actions">
                <a href="#" class="quick-action-btn btn-online-payment" data-bs-toggle="modal" data-bs-target="#onlinePaymentModal">
                    <i class="fas fa-credit-card"></i>
                    <span>Online Payment</span>
                </a>
                <a href="#" class="quick-action-btn btn-hospital" data-bs-toggle="modal" data-bs-target="#hospitalPaymentModal">
                    <i class="fas fa-hospital"></i>
                    <span>Hospital Services</span>
                </a>
                <a href="{{ route('patient-portal.index') }}" class="quick-action-btn btn-patient-portal" style="background: linear-gradient(135deg, #20c997, #1aa179);">
                    <i class="fas fa-user"></i>
                    <span>Patient Portal</span>
                </a>
            </div>

            <div class="register-link">
                <p class="mb-2">Don't have an account?</p>
                <a href="{{ route('applicant.register') }}">Apply Now</a>
            </div>
        </div>
    </div>
</div>

<!-- Online Payment Modal -->
<div class="modal fade" id="onlinePaymentModal" tabindex="-1" aria-labelledby="onlinePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white;">
                <h5 class="modal-title" id="onlinePaymentModalLabel">
                    <i class="fas fa-credit-card me-2"></i>Online Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="onlinePaymentForm">
                    @csrf
                    <input type="hidden" name="student_id" id="student_id">

                    <!-- Step 1: Payment Types -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type_id" id="payment_type_id" class="form-select" required>
                            <option value="">Select Payment Type</option>
                            @php
                            // Show active payment types from payment_types table, excluding application fees
                            // Application fees should be paid through the applicant portal
                            $paymentTypes = \App\Models\PaymentType::where('is_active', true)
                                ->where('purpose', '!=', \App\Models\PaymentType::PURPOSE_APPLICATION)
                                ->orderBy('priority')
                                ->orderBy('name')
                                ->get();
                            @endphp
                            @forelse($paymentTypes as $type)
                            @php
                            $portalChargePercent = 2.00;
                            $portalCharge = ($type->amount * $portalChargePercent) / 100;
                            @endphp
                            <option value="{{ $type->id }}"
                                    data-amount="{{ $type->amount }}"
                                    data-portal-charge="{{ $portalCharge }}"
                                    data-portal-charge-percent="{{ $portalChargePercent }}"
                                    data-is-editable="0">
                                {{ $type->name }} - ₦{{ number_format($type->amount) }}
                                @if($type->purpose)
                                    ({{ ucfirst(\App\Models\PaymentType::getPurposes()[$type->purpose] ?? $type->purpose) }})
                                @endif
                            </option>
                            @empty
                            <option value="">No payment types available</option>
                            @endforelse
                        </select>
                    </div>

                    <!-- Step 2: Payer ID -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Payer ID <span class="text-danger">*</span></label>
                        <input type="text" name="payer_id" id="payer_id" class="form-control" placeholder="Enter Matric Number, Phone Number, or Registration Number" required>
                        <small class="text-muted">Examples: ND/2024/001, 08012345678, REG/2024/001</small>
                        <div id="payerInfo" class="alert alert-info mt-2" style="display: none;"></div>
                    </div>

                    <!-- Step 3: Personal Information -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="payer_name" id="payer_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="payer_email" id="payer_email" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="payer_phone" id="payer_phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Payment Purpose <span class="text-danger">*</span></label>
                            <input type="text" name="payment_purpose" id="payment_purpose" class="form-control" readonly required>
                        </div>
                    </div>

                    <!-- Step 4: Amount -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Amount <span class="text-danger">*</span></label>
                            <input type="number" name="amount" id="amount" class="form-control" readonly required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Portal Charge (2%)</label>
                            <input type="number" name="portal_charge" id="portal_charge" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Total Amount</label>
                            <input type="number" name="total_amount" id="total_amount" class="form-control" readonly style="font-weight: bold; color: var(--primary);">
                        </div>
                    </div>

                    <!-- Step 5: Payment Method -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="online">Pay Online (Card/USSD/Bank)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-lock me-2"></i>Pay Now
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#validatePaymentModal">
                            <i class="fas fa-check-circle me-2"></i>Validate Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Validate Payment Modal -->
<div class="modal fade" id="validatePaymentModal" tabindex="-1" aria-labelledby="validatePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #247D57, #1E6A4A); color: white;">
                <h5 class="modal-title" id="validatePaymentModalLabel">
                    <i class="fas fa-search me-2"></i>Validate Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="validatePaymentForm">
                    <div class="mb-3">
                        <label class="form-label">Payment Reference</label>
                        <input type="text" name="payment_reference" id="validate_payment_ref" class="form-control" placeholder="Enter payment reference" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-search me-2"></i>Validate
                        </button>
                    </div>
                </form>
                <div id="validationResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Hospital Payment Modal -->
<div class="modal fade" id="hospitalPaymentModal" tabindex="-1" aria-labelledby="hospitalPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                <h5 class="modal-title" id="hospitalPaymentModalLabel">
                    <i class="fas fa-hospital me-2"></i>Hospital Services Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="hospitalPaymentForm">
                    @csrf

                    <!-- Patient Information -->
                    <h6 class="fw-bold text-danger mb-3">Patient Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="patient_name" id="patient_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="patient_phone" id="patient_phone" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="patient_email" id="patient_email" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="patient_gender" id="patient_gender" class="form-select">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" name="patient_age" id="patient_age" class="form-control" min="0" max="150">
                        </div>
                    </div>

                    <!-- Service Type -->
                    <h6 class="fw-bold text-danger mb-3 mt-4">Service Details</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Service <span class="text-danger">*</span></label>
                        <select name="service_type_id" id="service_type_id" class="form-select" required>
                            <option value="">Select Hospital Service</option>
                            @php
                            $serviceCategories = [
                                'Registration' => 'Hospital Registration',
                                'Consultation' => 'Consultation',
                                'Laboratory' => 'Laboratory/Test',
                                'Pharmacy' => 'Pharmacy',
                                'Radiology' => 'X-Ray/Scan',
                                'Admission' => 'Admission',
                                'Others' => 'Other Services'
                            ];
                            @endphp
                            @foreach($serviceCategories as $category => $label)
                            <optgroup label="{{ $label }}">
                                @php
                                $services = \App\Models\Hospital\HospitalServiceType::active()
                                    ->where('category', $category)
                                    ->get();
                                @endphp
                                @foreach($services as $service)
                                <option value="{{ $service->id }}" data-amount="{{ $service->amount }}" data-requires-appointment="{{ $service->requires_appointment ? '1' : '0' }}">
                                    {{ $service->name }} - ₦{{ number_format($service->amount) }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        @if(\App\Models\Hospital\HospitalServiceType::active()->count() == 0)
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>No hospital services configured. Please contact the hospital administration.
                        </div>
                        @endif
                    </div>

                    <!-- Appointment Details (conditional) -->
                    <div id="appointmentFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Appointment Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="appointment_date" id="appointment_date" class="form-control" min="{{ now()->format('Y-m-d\TH:i') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Preferred Doctor (Optional)</label>
                                <input type="text" name="doctor_name" id="doctor_name" class="form-control" placeholder="If any">
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any additional information..."></textarea>
                    </div>

                    <!-- Amount -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Amount</label>
                            <input type="number" name="amount" id="hospital_amount" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Portal Charge (2%)</label>
                            <input type="number" name="portal_charge" id="hospital_portal_charge" class="form-control" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Total Amount</label>
                            <input type="number" name="total_amount" id="hospital_total_amount" class="form-control" readonly style="font-weight: bold; color: #dc3545;">
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Payment Method <span class="text-danger">*</span></label>
                        <select name="payment_method" id="hospital_payment_method" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="online">Pay Online (Card/USSD/Bank)</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fas fa-credit-card me-2"></i>Pay Now
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#hospitalValidateModal">
                            <i class="fas fa-check-circle me-2"></i>Validate Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hospital Validate Payment Modal -->
<div class="modal fade" id="hospitalValidateModal" tabindex="-1" aria-labelledby="hospitalValidateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white;">
                <h5 class="modal-title" id="hospitalValidateModalLabel">
                    <i class="fas fa-search me-2"></i>Validate Hospital Payment
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="hospitalValidateForm">
                    <div class="mb-3">
                        <label class="form-label">Payment Reference</label>
                        <input type="text" name="payment_reference" id="hospital_payment_ref" class="form-control" placeholder="Enter payment reference (e.g., HSP-XXXXXX)" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-search me-2"></i>Validate
                        </button>
                    </div>
                </form>
                <div id="hospitalValidationResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // When payment type is selected
    $('#payment_type_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var amount = selectedOption.data('amount') || 0;
        var portalCharge = selectedOption.data('portal-charge') || 0;
        var isEditable = selectedOption.data('is-editable') || 0;

        $('#payment_purpose').val(selectedOption.text().trim());
        $('#amount').val(amount);
        $('#portal_charge').val(portalCharge);
        $('#total_amount').val(parseFloat(amount) + parseFloat(portalCharge));

        // Make amount and purpose editable if configured
        if(isEditable == 1) {
            $('#amount').prop('readonly', false);
            $('#payment_purpose').prop('readonly', false);
        } else {
            $('#amount').prop('readonly', true);
            $('#payment_purpose').prop('readonly', true);
        }
    });

    // Update total when amount changes (if editable)
    $('#amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        var portalCharge = parseFloat($('#portal_charge').val()) || 0;
        $('#total_amount').val(amount + portalCharge);
    });

    // When payer ID is entered - lookup student
    $('#payer_id').blur(function() {
        var payerId = $(this).val();
        if(payerId.length < 3) return;

        $.ajax({
            url: '{{ route("online-payment.lookup") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                payer_id: payerId
            },
            success: function(response) {
                if(response.success) {
                    $('#student_id').val(response.student_id);
                    $('#payer_name').val(response.name);
                    $('#payer_email').val(response.email);
                    $('#payer_phone').val(response.phone);
                    $('#payerInfo').show().html('<i class="fas fa-check-circle"></i> <strong>Found:</strong> ' + response.name + ' (' + response.matric_number + ')').removeClass('alert-info').addClass('alert-success');
                } else {
                    $('#payerInfo').show().html('<i class="fas fa-info-circle"></i> ' + response.message).removeClass('alert-success').addClass('alert-info');
                }
            },
            error: function() {
                $('#payerInfo').show().html('<i class="fas fa-exclamation-triangle"></i> Error looking up student').addClass('alert-warning');
            }
        });
    });

    // Submit online payment
    $('#onlinePaymentForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("online-payment.process") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    alert('Payment initiated successfully! Reference: ' + response.reference);
                    // Show print receipt option
                    if(response.receipt_url) {
                        window.open(response.receipt_url, '_blank');
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error processing payment');
            }
        });
    });

    // Validate payment
    $('#validatePaymentForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("online-payment.validate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    $('#validationResult').html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> Payment Verified!<br>' +
                        '<strong>Name:</strong> ' + response.payment.payer_name + '<br>' +
                        '<strong>Amount:</strong> ₦' + response.payment.amount + '<br>' +
                        '<strong>Date:</strong> ' + response.payment.created_at + '</div>');
                } else {
                    $('#validationResult').html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.message + '</div>');
                }
            }
        });
    });

    // ============ HOSPITAL PAYMENT FUNCTIONALITY ============

    // When service type is selected
    $('#service_type_id').change(function() {
        var selectedOption = $(this).find('option:selected');
        var amount = selectedOption.data('amount') || 0;
        var portalCharge = (amount * 2) / 100;
        var requiresAppointment = selectedOption.data('requires-appointment') || 0;

        $('#hospital_amount').val(amount);
        $('#hospital_portal_charge').val(portalCharge);
        $('#hospital_total_amount').val(parseFloat(amount) + parseFloat(portalCharge));

        // Show/hide appointment fields
        if(requiresAppointment == 1) {
            $('#appointmentFields').show();
            $('#appointment_date').prop('required', true);
        } else {
            $('#appointmentFields').hide();
            $('#appointment_date').prop('required', false);
        }
    });

    // Submit hospital payment
    $('#hospitalPaymentForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("hospital-payment.process") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    alert('Payment initiated successfully! Reference: ' + response.reference);
                    if(response.receipt_url) {
                        window.open(response.receipt_url, '_blank');
                    }
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error processing payment');
            }
        });
    });

    // Validate hospital payment
    $('#hospitalValidateForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: '{{ route("hospital-payment.validate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    $('#hospitalValidationResult').html('<div class="alert alert-success"><i class="fas fa-check-circle"></i> Payment Verified!<br>' +
                        '<strong>Patient:</strong> ' + response.payment.patient_name + '<br>' +
                        '<strong>Service:</strong> ' + response.payment.service_name + '<br>' +
                        '<strong>Amount:</strong> ₦' + response.payment.amount + '<br>' +
                        '<strong>Date:</strong> ' + response.payment.created_at + '</div>');
                } else {
                    $('#hospitalValidationResult').html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ' + response.message + '</div>');
                }
            }
        });
    });
});
</script>
@endpush