@extends('layouts.app')

@section('title', 'Edit Payment Regime')

@section('content')
<div class="page-header">
    <h4>Edit Payment Regime</h4>
    <p class="text-muted">Configure payment rules for indigene and non-indigene students</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('bursar.regimes.update', $regime) }}">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <h5 class="mb-3"><i class="fas fa-cog me-2"></i>Basic Information</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Regime Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @endif"
                               id="name" name="name" value="{{ old('name', $regime->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="payment_type" class="form-label">Payment Type *</label>
                        <select class="form-select @error('payment_type') is-invalid @endif"
                                id="payment_type" name="payment_type" required>
                            <option value="school_fee" {{ $regime->payment_type == 'school_fee' ? 'selected' : '' }}>School Fee</option>
                            <option value="acceptance_fee" {{ $regime->payment_type == 'acceptance_fee' ? 'selected' : '' }}>Acceptance Fee</option>
                            <option value="accommodation" {{ $regime->payment_type == 'accommodation' ? 'selected' : '' }}>Accommodation</option>
                            <option value="other" {{ $regime->payment_type == 'other' ? 'selected' : '' }}>Other Fee</option>
                        </select>
                        @error('payment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="student_type" class="form-label">Student Type *</label>
                        <select class="form-select @error('student_type') is-invalid @endif"
                                id="student_type" name="student_type" required>
                            <option value="Indigene" {{ $regime->student_type == 'Indigene' ? 'selected' : '' }}>Indigene</option>
                            <option value="Non-Indigene" {{ $regime->student_type == 'Non-Indigene' ? 'selected' : '' }}>Non-Indigene</option>
                        </select>
                        @error('student_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="payment_config" class="form-label">Payment Configuration *</label>
                        <select class="form-select @error('payment_config') is-invalid @endif"
                                id="payment_config" name="payment_config" required>
                            <option value="full" {{ $regime->payment_config == 'full' ? 'selected' : '' }}>Full Payment (100%)</option>
                            <option value="60_40" {{ $regime->payment_config == '60_40' ? 'selected' : '' }}>60% First, 40% Second</option>
                            <option value="70_30" {{ $regime->payment_config == '70_30' ? 'selected' : '' }}>70% First, 30% Second</option>
                            <option value="50_50" {{ $regime->payment_config == '50_50' ? 'selected' : '' }}>50% First, 50% Second</option>
                        </select>
                        @error('payment_config')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Portal Charges --}}
            <h5 class="mb-3 mt-4"><i class="fas fa-credit-card me-2"></i>Portal Charges</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="portal_charge" class="form-label">Portal Charge (₦)</label>
                        <input type="number" class="form-control @error('portal_charge') is-invalid @endif"
                               id="portal_charge" name="portal_charge" value="{{ old('portal_charge', $regime->portal_charge ?? 0) }}" min="0" step="0.01">
                        @error('portal_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="include_portal_charge" name="include_portal_charge" value="1" {{ $regime->include_portal_charge ? 'checked' : '' }}>
                            <label class="form-check-label" for="include_portal_charge">Include portal charge in payment</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Payment Amount --}}
            <h5 class="mb-3 mt-4"><i class="fas fa-money-bill me-2"></i>Payment Amount</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="installment" class="form-label">Installment *</label>
                        <select class="form-select @error('installment') is-invalid @endif"
                                id="installment" name="installment" required>
                            <option value="Full" {{ $regime->installment == 'Full' ? 'selected' : '' }}>Full Payment</option>
                            <option value="First" {{ $regime->installment == 'First' ? 'selected' : '' }}>First Installment</option>
                            <option value="Second" {{ $regime->installment == 'Second' ? 'selected' : '' }}>Second Installment</option>
                        </select>
                        @error('installment')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="percentage" class="form-label">Percentage (%) *</label>
                        <input type="number" class="form-control @error('percentage') is-invalid @endif"
                               id="percentage" name="percentage" value="{{ old('percentage', $regime->percentage) }}" required min="1" max="100">
                        @error('percentage')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Fixed Amount (Optional)</label>
                        <input type="number" class="form-control @error('amount') is-invalid @endif"
                               id="amount" name="amount" value="{{ old('amount', $regime->amount) }}" min="0" step="0.01"
                               placeholder="Leave empty to calculate">
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Scope (Optional) --}}
            <h5 class="mb-3 mt-4"><i class="fas fa-filter me-2"></i>Payment Scope (Optional)</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">School</label>
                        <select class="form-select" id="school_id" name="school_id">
                            <option value="">All Schools</option>
                            @foreach($schools as $school)
                            <option value="{{ $school->id }}" {{ $regime->school_id == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ $regime->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme</label>
                        <select class="form-select" id="programme_id" name="programme_id">
                            <option value="">All Programmes</option>
                            @foreach($programmes as $prog)
                            <option value="{{ $prog->id }}" {{ $regime->programme_id == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session</label>
                        <select class="form-select" id="session_id" name="session_id">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                            <option value="{{ $session->id }}" {{ $regime->session_id == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester">
                            <option value="">All Semesters</option>
                            <option value="first" {{ $regime->semester == 'first' ? 'selected' : '' }}>First Semester</option>
                            <option value="second" {{ $regime->semester == 'second' ? 'selected' : '' }}>Second Semester</option>
                            <option value="both" {{ $regime->semester == 'both' ? 'selected' : '' }}>Both Semesters</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level</label>
                        <select class="form-select" id="level" name="level">
                            <option value="">All Levels</option>
                            <option value="1" {{ $regime->level == 1 ? 'selected' : '' }}>100L / ND1</option>
                            <option value="2" {{ $regime->level == 2 ? 'selected' : '' }}>200L / ND2</option>
                            <option value="3" {{ $regime->level == 3 ? 'selected' : '' }}>300L / HND1</option>
                            <option value="4" {{ $regime->level == 4 ? 'selected' : '' }}>400L / HND2</option>
                            <option value="5" {{ $regime->level == 5 ? 'selected' : '' }}>500L</option>
                            <option value="6" {{ $regime->level == 6 ? 'selected' : '' }}>600L</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="level_operator" class="form-label">Level Match</label>
                        <select class="form-select" id="level_operator" name="level_operator">
                            <option value="exact" {{ $regime->level_operator == 'exact' ? 'selected' : '' }}>Exact Match</option>
                            <option value="minimum" {{ $regime->level_operator == 'minimum' ? 'selected' : '' }}>This Level & Above</option>
                            <option value="maximum" {{ $regime->level_operator == 'maximum' ? 'selected' : '' }}>This Level & Below</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3 form-check mt-4">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ $regime->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active"><strong>Active</strong> - Enable this payment regime</label>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i>Update Payment Regime
                </button>
                <a href="{{ route('bursar.regimes.index') }}" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection