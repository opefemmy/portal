@extends('layouts.app')

@section('title', 'Create Fee')

@section('content')
<div class="page-header">
    <h4>Create New Fee</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.fees.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Fee Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="payment_type" class="form-label">Payment Type</label>
                        <select class="form-select @error('payment_type') is-invalid @enderror"
                                id="payment_type" name="payment_type" required>
                            <option value="">Select Payment Type</option>
                            <option value="Tuition Fee">Tuition Fee</option>
                            <option value="Departmental Fee">Departmental Fee</option>
                            <option value="Other">Other</option>
                        </select>
                        @error('payment_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="session_id" class="form-label">Session</label>
                        <select class="form-select @error('session_id') is-invalid @enderror"
                                id="session_id" name="session_id" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }} - {{ $session->semester }}</option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">Student Category</label>
                        <select class="form-select @error('category') is-invalid @enderror"
                                id="category" name="category">
                            <option value="both">All Students (Indigene &amp; Non-Indigene)</option>
                            <option value="indigene">Indigene Only</option>
                            <option value="non_indigene">Non-Indigene Only</option>
                            <option value="portal_charge">Portal Charges (All Students)</option>
                        </select>
                        <small class="text-muted">
                            Switching category reveals the matching amount fields below.
                        </small>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Single set of amount inputs that always exist in the
                 DOM. No more display:none toggling, no more duplicate
                 `name=` attributes across hidden rows. Server-side
                 validation in FeeController::store() already enforces
                 which amounts are required per category. The JS at the
                 bottom of this view only swaps the inline help text. --}}
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="indigene_amount" class="form-label">
                            Indigene Amount (₦)
                            <span class="text-danger" id="indigene_required_marker">*</span>
                        </label>
                        <input type="number" class="form-control @error('indigene_amount') is-invalid @enderror"
                               id="indigene_amount" name="indigene_amount"
                               value="{{ old('indigene_amount') }}" min="0" step="0.01"
                               placeholder="Fee paid by indigene students">
                        @error('indigene_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="non_indigene_amount" class="form-label">
                            Non-Indigene Amount (₦)
                            <span class="text-danger" id="non_indigene_required_marker">*</span>
                        </label>
                        <input type="number" class="form-control @error('non_indigene_amount') is-invalid @enderror"
                               id="non_indigene_amount" name="non_indigene_amount"
                               value="{{ old('non_indigene_amount') }}" min="0" step="0.01"
                               placeholder="Fee paid by non-indigene students">
                        @error('non_indigene_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="portal_charge" class="form-label">Portal Charges (₦)</label>
                        <input type="number" class="form-control @error('portal_charge') is-invalid @enderror"
                               id="portal_charge" name="portal_charge"
                               value="{{ old('portal_charge', 0) }}" min="0" step="0.01"
                               placeholder="Applies to the selected audience">
                        <small class="text-muted" id="portal_charge_help">Applies to all students.</small>
                        @error('portal_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Hidden fallback: keeps the legacy `amount` column populated
                 so existing queries and reports that read `amount` keep
                 working. The form mirrors the most-relevant visible
                 amount into this field on submit. --}}
            <input type="hidden" name="amount" id="amount_legacy" value="{{ old('amount', 0) }}">

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="school_id" class="form-label">School (Optional)</label>
                        <select class="form-select @error('school_id') is-invalid @enderror"
                                id="school_id" name="school_id">
                            <option value="">All Schools</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->id }}">{{ $school->name }}</option>
                            @endforeach
                        </select>
                        @error('school_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="department_id" class="form-label">Department (Optional)</label>
                        <select class="form-select @error('department_id') is-invalid @enderror"
                                id="department_id" name="department_id">
                            <option value="">All Departments</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="programme_id" class="form-label">Programme (Optional)</label>
                        <select class="form-select @error('programme_id') is-invalid @enderror"
                                id="programme_id" name="programme_id">
                            <option value="">All Programmes</option>
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                            @endforeach
                        </select>
                        @error('programme_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="level" class="form-label">Level (Optional)</label>
                        <select class="form-select @error('level') is-invalid @enderror"
                                id="level" name="level">
                            <option value="">All Levels</option>
                            <option value="1">100L / ND1</option>
                            <option value="2">200L / ND</option>
                            <option value="3">300L / HND1</option>
                            <option value="4">400L / HND2</option>
                            <option value="5">500L</option>
                            <option value="6">600L</option>
                        </select>
                        @error('level')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                               id="due_date" name="due_date" value="{{ old('due_date') }}">
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3 form-check mt-4">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Create Fee
                </button>
                <a href="{{ route('admin.fees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const category = document.getElementById('category');
    const indigeneMarker = document.getElementById('indigene_required_marker');
    const nonIndigeneMarker = document.getElementById('non_indigene_required_marker');
    const portalHelp = document.getElementById('portal_charge_help');
    const legacyAmount = document.getElementById('amount_legacy');
    const indigeneInput = document.getElementById('indigene_amount');
    const nonIndigeneInput = document.getElementById('non_indigene_amount');

    // Per-category configuration. Only the marker visibility and the
    // legacy mirror change — the form values themselves are always in the
    // DOM and the controller decides which are required.
    const config = {
        both:         { indigene: true,  nonIndigene: true,  help: 'Applies to all students.' },
        indigene:     { indigene: true,  nonIndigene: false, help: 'Applies to indigene students.' },
        non_indigene: { indigene: false, nonIndigene: true,  help: 'Applies to non-indigene students.' },
        portal_charge:{ indigene: false, nonIndigene: false, help: 'Portal charge only — the amounts above are ignored.' },
    };

    function refresh() {
        const value = category.value || 'both';
        const c = config[value] || config.both;

        // Required-marker asterisks: only show the * for amounts that
        // the server-side validation will require. Optional amounts
        // stay visible but lose the marker so admins aren't forced to
        // fill in unused fields.
        if (indigeneMarker)    indigeneMarker.style.display    = c.indigene    ? '' : 'none';
        if (nonIndigeneMarker) nonIndigeneMarker.style.display = c.nonIndigene ? '' : 'none';
        if (portalHelp)        portalHelp.textContent          = c.help;

        // Mirror the relevant amount into the legacy `amount` hidden
        // input. For `portal_charge` category the legacy column isn't
        // meaningful so leave it at 0.
        if (legacyAmount) {
            if (value === 'portal_charge') {
                legacyAmount.value = 0;
            } else if (value === 'non_indigene') {
                legacyAmount.value = nonIndigeneInput && nonIndigeneInput.value ? nonIndigeneInput.value : 0;
            } else {
                legacyAmount.value = indigeneInput && indigeneInput.value ? indigeneInput.value : 0;
            }
        }
    }

    if (indigeneInput) {
        indigeneInput.addEventListener('input', refresh);
    }
    if (nonIndigeneInput) {
        nonIndigeneInput.addEventListener('input', refresh);
    }
    if (category) {
        category.addEventListener('change', refresh);
    }

    refresh();
});
</script>
@endsection