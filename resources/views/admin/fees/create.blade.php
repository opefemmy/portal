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

            {{-- Per-category amount fields. Visibility is driven by the
                 Student Category select via the inline JS at the bottom
                 of this view. The legacy `amount` field is kept (hidden)
                 for back-compat with existing rows. --}}
            <div id="amount-both" class="row fee-amount-row" data-show-when="both">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="indigene_amount" class="form-label">Indigene Amount (₦)</label>
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
                        <label for="non_indigene_amount" class="form-label">Non-Indigene Amount (₦)</label>
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
                        <label for="portal_charge_both" class="form-label">Portal Charges (₦)</label>
                        <input type="number" class="form-control @error('portal_charge') is-invalid @enderror"
                               id="portal_charge_both" name="portal_charge"
                               value="{{ old('portal_charge', 0) }}" min="0" step="0.01"
                               placeholder="Applies to all students">
                        @error('portal_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div id="amount-indigene" class="row fee-amount-row" data-show-when="indigene">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="indigene_amount_only" class="form-label">Indigene Amount (₦)</label>
                        <input type="number" class="form-control @error('indigene_amount') is-invalid @enderror"
                               id="indigene_amount_only" name="indigene_amount"
                               value="{{ old('indigene_amount') }}" min="0" step="0.01"
                               placeholder="Fee paid by indigene students">
                        @error('indigene_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="portal_charge_indigene" class="form-label">Portal Charges (₦)</label>
                        <input type="number" class="form-control @error('portal_charge') is-invalid @enderror"
                               id="portal_charge_indigene" name="portal_charge"
                               value="{{ old('portal_charge', 0) }}" min="0" step="0.01"
                               placeholder="Applies to indigene students">
                        @error('portal_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div id="amount-non_indigene" class="row fee-amount-row" data-show-when="non_indigene">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="non_indigene_amount_only" class="form-label">Non-Indigene Amount (₦)</label>
                        <input type="number" class="form-control @error('non_indigene_amount') is-invalid @enderror"
                               id="non_indigene_amount_only" name="non_indigene_amount"
                               value="{{ old('non_indigene_amount') }}" min="0" step="0.01"
                               placeholder="Fee paid by non-indigene students">
                        @error('non_indigene_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="portal_charge_non_indigene" class="form-label">Portal Charges (₦)</label>
                        <input type="number" class="form-control @error('portal_charge') is-invalid @enderror"
                               id="portal_charge_non_indigene" name="portal_charge"
                               value="{{ old('portal_charge', 0) }}" min="0" step="0.01"
                               placeholder="Applies to non-indigene students">
                        @error('portal_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div id="amount-portal_charge" class="row fee-amount-row" data-show-when="portal_charge">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="portal_charge_only" class="form-label">Portal Charges Only (₦)</label>
                        <input type="number" class="form-control @error('portal_charge') is-invalid @enderror"
                               id="portal_charge_only" name="portal_charge"
                               value="{{ old('portal_charge', 0) }}" min="0" step="0.01"
                               placeholder="Portal charge amount">
                        @error('portal_charge')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Hidden fallback: keeps `amount` populated so existing rows
                 that still rely on the legacy column keep working. The
                 controller copies the relevant per-category value into
                 `amount` when category != portal_charge. --}}
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
    const rows = document.querySelectorAll('.fee-amount-row');
    const legacyAmount = document.getElementById('amount_legacy');

    function refresh() {
        const value = category.value || 'both';
        rows.forEach(function (row) {
            row.style.display = row.dataset.showWhen === value ? '' : 'none';
        });

        // Mirror the visible amount field into the legacy `amount` hidden
        // input so the controller's `amount` column stays populated.
        const visibleAmount = document.querySelector(
            '.fee-amount-row:not([style*="display: none"]) input[type="number"]:not([name="portal_charge"])'
        );
        if (visibleAmount && legacyAmount) {
            legacyAmount.value = visibleAmount.value || 0;
            visibleAmount.addEventListener('input', function () {
                legacyAmount.value = visibleAmount.value || 0;
            });
        }
    }

    category.addEventListener('change', refresh);
    refresh();
});
</script>
@endsection