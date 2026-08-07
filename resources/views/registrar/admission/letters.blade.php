@extends('layouts.app')

@section('title', 'Generate Admission Letters')

@php
    use App\Models\SystemSetting;
    $institutionName = SystemSetting::get('institution_name', 'Ekiti State College of Technology');
    $institutionAddress = SystemSetting::get('institution_address', '');
    $institutionPhone = SystemSetting::get('institution_phone', '');
    $institutionEmail = SystemSetting::get('institution_email', '');
    $institutionWebsite = SystemSetting::get('institution_website', '');
    $registrarSignature = SystemSetting::get('registrar_signature_path', null);
    $registrarName = SystemSetting::get('registrar_name');
    if (! $registrarName) {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('users') && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
                $registrar = \App\Models\User::whereHas('role', function ($q) {
                    $q->where('slug', 'registrar');
                })->first();
                $registrarName = $registrar?->name;
            }
        } catch (\Throwable $e) {
            $registrarName = null;
        }
    }
    $letterBody = SystemSetting::get('admission_letter_body', "We are pleased to inform you that you have been offered provisional admission into the {programme} programme of the {department}, {school}, for the {session} academic session.\n\nPlease complete the acceptance process by paying the required fees listed below before the deadline. On behalf of the institution, we congratulate you and look forward to welcoming you on campus.");
    $letterFeesRaw = SystemSetting::get('admission_letter_fees', '[]');
    $letterFees = json_decode($letterFeesRaw, true);
    if (!is_array($letterFees)) { $letterFees = []; }
@endphp

@section('content')
<div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h4 class="mb-0"><i class="fas fa-file-signature me-2"></i>Generate Admission Letters</h4>
        <p class="text-muted mb-0">Build the letter template, attach fees, and print for admitted applicants</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('registrar.admission.byDepartment') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        @if(session('error') && str_contains(session('error'), 'signature'))
            <div class="mt-2 small">
                <strong>Likely cause:</strong> the <code>storage/app/public/signatures</code> directory
                is not writable by PHP. On Linux run
                <code>chown -R www-data:www-data storage/app/public</code> (or the PHP-FPM user).
            </div>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('registrar.admission.saveLetterSettings') }}" enctype="multipart/form-data">
    @csrf

    <div class="row">
        {{-- LEFT: Letter template editor + fee list --}}
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-pen-fancy me-2"></i>Letter Body</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-2">
                        Use these placeholders anywhere in the body — they will be replaced per applicant when the letter is rendered:
                    </p>
                    <div class="bg-light p-2 rounded small mb-3">
                        <code>{name}</code>, <code>{programme}</code>, <code>{department}</code>, <code>{school}</code>,
                        <code>{session}</code>, <code>{matric_number}</code>, <code>{admission_date}</code>
                    </div>
                    <textarea name="admission_letter_body" rows="8" class="form-control">{{ $letterBody }}</textarea>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Acceptance Fees (attached to letter)</h5>
                    <button type="button" class="btn btn-sm btn-light" id="addFeeRow">
                        <i class="fas fa-plus me-1"></i>Add Fee
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        These fees appear on every letter as a fee schedule and are bundled into the downloadable print-out. Leave blank for none.
                    </p>
                    <div id="feesContainer">
                        @forelse($letterFees as $i => $fee)
                        <div class="fee-row row g-2 mb-2">
                            <div class="col-md-6">
                                <input type="text" name="fees[{{ $i }}][name]" class="form-control" placeholder="Fee name (e.g. Acceptance Fee)" value="{{ $fee['name'] ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="fees[{{ $i }}][amount]" class="form-control" placeholder="Amount (₦)" min="0" step="0.01" value="{{ $fee['amount'] ?? '' }}">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger remove-fee w-100" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @empty
                        <div class="fee-row row g-2 mb-2">
                            <div class="col-md-6">
                                <input type="text" name="fees[0][name]" class="form-control" placeholder="Fee name (e.g. Acceptance Fee)">
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="fees[0][amount]" class="form-control" placeholder="Amount (₦)" min="0" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger remove-fee w-100" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-university me-2"></i>Letterhead (Institution Details)</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">These values populate the letterhead at the top of every printed letter.</p>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Institution Name</label>
                            <input type="text" name="institution_name" class="form-control" value="{{ $institutionName }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Address</label>
                            <input type="text" name="institution_address" class="form-control" value="{{ $institutionAddress }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="institution_phone" class="form-control" value="{{ $institutionPhone }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="institution_email" class="form-control" value="{{ $institutionEmail }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Website</label>
                            <input type="text" name="institution_website" class="form-control" value="{{ $institutionWebsite }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Letter Settings
                </button>
            </div>
        </div>

        {{-- RIGHT: Registrar signature + preview generator --}}
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-signature me-2"></i>Registrar Signature</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="registrar_name" class="form-label">Registrar Name</label>
                        <input type="text" id="registrar_name" name="registrar_name"
                               class="form-control @error('registrar_name') is-invalid @enderror"
                               value="{{ old('registrar_name', $registrarName) }}"
                               placeholder="e.g. Dr. A. B. Registrar">
                        <small class="text-muted">
                            This is the name shown under the signature on every printed admission letter. If left blank, the system falls back to the user account with the <code>registrar</code> role.
                        </small>
                    </div>

                    <hr>

                    <p class="text-muted small mb-3">
                        Upload your signature image (PNG/JPG with transparent background recommended). It will appear as the signee at the bottom of every letter.
                    </p>
                    @if($registrarSignature && file_exists(public_path('storage/' . $registrarSignature)))
                        <div class="text-center mb-3 p-3 border rounded bg-white">
                            <img src="{{ asset('storage/' . $registrarSignature) }}" alt="Signature" style="max-height: 80px;">
                            <div class="mt-2">
                                <small class="text-muted d-block">Current signature</small>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="deleteSignatureBtn">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    @endif
                    <input type="file" name="registrar_signature" id="registrar_signature_input"
                           class="form-control" accept="image/*">
                    <small class="text-muted">
                        Max 2MB. PNG/JPG/SVG.
                        <span class="text-info ms-1">
                            <i class="fas fa-info-circle"></i> Saving is automatic — the form submits as soon as you pick a file.
                        </span>
                    </small>

                    {{-- Inline Save button right under the signature upload so the
                         admin never has to scroll back to the left column to find it.
                         The form submit fires on file-select too (see @push scripts),
                         so this button is just an explicit fallback. --}}
                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        <i class="fas fa-save me-2"></i>Save Letter Settings
                    </button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-image me-2"></i>Institution Logo (Watermark)</h5>
                </div>
                <div class="card-body text-center">
                    <img src="{{ asset('images/logo.png') }}?v={{ time() }}" alt="Logo" class="img-fluid mb-2" style="max-height: 100px;">
                    <p class="text-muted small mb-0">
                        The logo at <code>public/images/logo.png</code> is rendered as a transparent watermark behind the letter content. Update the file to change the watermark.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>

{{-- Print Letters lives OUTSIDE the Save form so its inner GET
     form does not nest inside the outer POST form. Nested forms
     are invalid HTML and Chrome/Firefox will silently drop the
     parent's submit button when the inner form closes; that was
     why "Save Letter Settings" appeared to do nothing. --}}
<div class="card mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-print me-2"></i>Print Letters</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('registrar.admission.generateLetters') }}" class="mb-3">
            <label class="form-label">Filter by Department</label>
            <select name="department_id" class="form-select mb-3" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-success w-100">
                <i class="fas fa-eye me-2"></i>Preview Letters
            </button>
        </form>
    </div>
</div>

{{-- Delete signature form (separate to avoid multipart issue with no file) --}}
@if($registrarSignature)
<form id="deleteSignatureForm" method="POST" action="{{ route('registrar.admission.deleteSignature') }}" class="d-none">
    @csrf
    @method('DELETE')
</form>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var feeIdx = {{ max(count($letterFees), 1) }};
    var feesContainer = document.getElementById('feesContainer');
    var addBtn = document.getElementById('addFeeRow');

    if (addBtn && feesContainer) {
        addBtn.addEventListener('click', function() {
            var firstRow = feesContainer.querySelector('.fee-row');
            var clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input').forEach(function(el) { el.value = ''; });
            clone.querySelectorAll('input[name*="[name]"]')[0].setAttribute('name', 'fees[' + feeIdx + '][name]');
            clone.querySelectorAll('input[name*="[amount]"]')[0].setAttribute('name', 'fees[' + feeIdx + '][amount]');
            feesContainer.appendChild(clone);
            feeIdx++;
        });

        feesContainer.addEventListener('click', function(e) {
            if (e.target.closest('.remove-fee')) {
                var rows = feesContainer.querySelectorAll('.fee-row');
                if (rows.length > 1) {
                    e.target.closest('.fee-row').remove();
                }
            }
        });
    }

    var deleteBtn = document.getElementById('deleteSignatureBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function() {
            if (confirm('Remove the current registrar signature?')) {
                document.getElementById('deleteSignatureForm').submit();
            }
        });
    }

    // Auto-save on signature upload. As soon as the registrar picks a
    // file, submit the outer form so the signature, name, body, fees
    // and letterhead all persist together — no need to scroll back to
    // the left column to find the Save button. We submit immediately;
    // the browser attaches the file before the submit handler runs.
    var sigInput = document.getElementById('registrar_signature_input');
    var outerForm = sigInput ? sigInput.closest('form') : null;
    if (sigInput && outerForm) {
        sigInput.addEventListener('change', function() {
            if (!sigInput.files || sigInput.files.length === 0) {
                return;
            }
            // Disable + show "Saving…" hint so the user sees feedback
            // before the redirect lands.
            sigInput.disabled = true;
            outerForm.submit();
        });
    }
});
</script>
@endpush