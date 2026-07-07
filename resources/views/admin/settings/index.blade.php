@extends('layouts.app')

@section('title', 'System Settings')

@php
use App\Models\SystemSetting;
@endphp

@section('content')
<div class="page-header">
    <h4>System Settings</h4>
</div>

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf

    {{-- Admission Settings --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>Admission Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Admission Form Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="admission_form_open" name="admission_form_open"
                                {{ SystemSetting::isOpen('admission_form_open') ? 'checked' : '' }}>
                            <label class="form-check-label" for="admission_form_open">Open for Applications</label>
                        </div>
                        <small class="text-muted">Enable to allow students to apply</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Late Application Penalty</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="admission_form_penalty" name="admission_form_penalty"
                                {{ SystemSetting::get('admission_form_penalty', 'false') === 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="admission_form_penalty">Enable Penalty</label>
                        </div>
                        <input type="number" name="admission_form_penalty_amount" class="form-control"
                            placeholder="Penalty Amount" value="{{ SystemSetting::get('admission_form_penalty_amount', 0) }}">
                        <small class="text-muted">Amount to pay for late application</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Course Registration Settings --}}
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Course Registration Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Course Registration Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="course_registration_open" name="course_registration_open"
                                {{ SystemSetting::isOpen('course_registration_open') ? 'checked' : '' }}>
                            <label class="form-check-label" for="course_registration_open">Open for Registration</label>
                        </div>
                        <small class="text-muted">Enable to allow students to register courses</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Late Registration Penalty</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="course_registration_penalty" name="course_registration_penalty"
                                {{ SystemSetting::get('course_registration_penalty', 'false') === 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="course_registration_penalty">Enable Penalty</label>
                        </div>
                        <input type="number" name="course_registration_penalty_amount" class="form-control"
                            placeholder="Penalty Amount" value="{{ SystemSetting::get('course_registration_penalty_amount', 0) }}">
                        <small class="text-muted">Amount to pay for late course registration</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Settings --}}
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-money-bill me-2"></i>Payment Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="payment_open" name="payment_open"
                                {{ SystemSetting::isOpen('payment_open') ? 'checked' : '' }}>
                            <label class="form-check-label" for="payment_open">Allow Payments</label>
                        </div>
                        <small class="text-muted">Enable to allow students to make payments</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Late Payment Penalty</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="payment_penalty" name="payment_penalty"
                                {{ SystemSetting::get('payment_penalty', 'false') === 'true' ? 'checked' : '' }}>
                            <label class="form-check-label" for="payment_penalty">Enable Penalty</label>
                        </div>
                        <input type="number" name="payment_penalty_amount" class="form-control"
                            placeholder="Penalty Amount" value="{{ SystemSetting::get('payment_penalty_amount', 0) }}">
                        <small class="text-muted">Amount to pay for late payment</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Late Fee Payment Settings --}}
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Late Payment Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- School Fee Late Payment --}}
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-school me-2"></i>School Fee</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="late_school_fee_enabled" name="late_school_fee_enabled"
                                    {{ SystemSetting::get('late_school_fee_enabled', 'false') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="late_school_fee_enabled">Enable Late Payment</label>
                            </div>
                            <input type="number" name="late_school_fee_amount" class="form-control"
                                placeholder="Late Fee Amount" value="{{ SystemSetting::get('late_school_fee_amount', 0) }}">
                            <small class="text-muted">Amount to pay for late school fee</small>
                        </div>
                    </div>
                </div>
                {{-- Course Registration Late Payment --}}
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-book me-2"></i>Course Reg Fee</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="late_course_reg_enabled" name="late_course_reg_enabled"
                                    {{ SystemSetting::get('late_course_reg_enabled', 'false') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="late_course_reg_enabled">Enable Late Payment</label>
                            </div>
                            <input type="number" name="late_course_reg_amount" class="form-control"
                                placeholder="Late Fee Amount" value="{{ SystemSetting::get('late_course_reg_amount', 0) }}">
                            <small class="text-muted">Amount to pay for late course registration</small>
                        </div>
                    </div>
                </div>
                {{-- Other Fees Late Payment --}}
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-money-bill-wave me-2"></i>Other Fees</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="late_other_fee_enabled" name="late_other_fee_enabled"
                                    {{ SystemSetting::get('late_other_fee_enabled', 'false') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="late_other_fee_enabled">Enable Late Payment</label>
                            </div>
                            <input type="number" name="late_other_fee_amount" class="form-control"
                                placeholder="Late Fee Amount" value="{{ SystemSetting::get('late_other_fee_amount', 0) }}">
                            <small class="text-muted">Amount to pay for late other fees</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Result Upload Settings --}}
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Result Upload Settings</h5>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Result Upload Status</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="result_upload_open" name="result_upload_open"
                        {{ SystemSetting::isOpen('result_upload_open') ? 'checked' : '' }}>
                    <label class="form-check-label" for="result_upload_open">Allow Lecturers to Upload Results</label>
                </div>
                <small class="text-muted">Enable to allow lecturers to enter and upload results</small>
            </div>
        </div>
    </div>

    {{-- Library Settings --}}
    <div class="card mb-4 border-purple" style="border-color: #6f42c1;">
        <div class="card-header bg-purple text-white" style="background: #6f42c1;">
            <h5 class="mb-0"><i class="fas fa-book-reader me-2"></i>Library Settings</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-money-bill me-2"></i>Library Fee</h6>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="library_fee_required" name="library_fee_required"
                                    {{ SystemSetting::get('library_fee_required', 'false') === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="library_fee_required">Require Library Fee</label>
                            </div>
                            <input type="number" name="library_fee_amount" class="form-control"
                                placeholder="Fee Amount" value="{{ SystemSetting::get('library_fee_amount', 0) }}">
                            <small class="text-muted">Amount students must pay to access library</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6><i class="fas fa-clock me-2"></i>Late Return Penalty</h6>
                            <div class="mb-3">
                                <label class="form-label">Late Fee Per Day</label>
                                <input type="number" name="library_late_fee_per_day" class="form-control"
                                    placeholder="Late Fee Per Day" value="{{ SystemSetting::get('library_late_fee_per_day', 100) }}">
                                <small class="text-muted">Penalty for late book return (per day)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Max Borrow Days</label>
                                <input type="number" name="library_max_borrow_days" class="form-control"
                                    placeholder="Max Borrow Days" value="{{ SystemSetting::get('library_max_borrow_days', 14) }}">
                                <small class="text-muted">Default maximum days to borrow a book</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Save All Settings
        </button>
    </div>
</form>

{{-- Payment Gateway Settings --}}
<div class="card mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Gateway Configuration</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.gateway') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Payment Provider</label>
                        <select name="provider" class="form-select">
                            <option value="paystack">Paystack</option>
                            <option value="flutterwave">Flutterwave</option>
                            <option value="stripe">Stripe</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Mode</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_test_mode" name="is_test_mode" checked>
                            <label class="form-check-label" for="is_test_mode">Test Mode (Use Test Keys)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Test Public Key</label>
                        <input type="text" name="test_public_key" class="form-control" placeholder="pk_test_...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Test Secret Key</label>
                        <input type="password" name="test_secret_key" class="form-control" placeholder="sk_test_...">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Live Public Key</label>
                        <input type="text" name="live_public_key" class="form-control" placeholder="pk_live_...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Live Secret Key</label>
                        <input type="password" name="live_secret_key" class="form-control" placeholder="sk_live_...">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-dark">
                <i class="fas fa-save me-2"></i>Save Gateway Settings
            </button>
        </form>
    </div>
</div>
@endsection