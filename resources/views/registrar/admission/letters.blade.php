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
                                {{-- Auto-save status — updated by the JS in @push('scripts'). --}}
                                <span class="fee-save-status save-status small text-muted mt-1" data-state="idle"></span>
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
                                <span class="fee-save-status save-status small text-muted mt-1" data-state="idle"></span>
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
                        {{-- Auto-save status — toggled by the JS in @push('scripts').
                             Targets registrar_name_status so the AJAX PATCH fires
                             as soon as the input loses focus. --}}
                        <small id="registrar_name_status" class="save-status small text-muted" data-state="idle"></small>
                        <small class="text-muted d-block">
                            This is the name shown under the signature on every printed admission letter. If left blank, the system falls back to the user account with the <code>registrar</code> role.
                        </small>
                    </div>

                    <hr>

                    <p class="text-muted small mb-3">
                        Upload your signature image (PNG/JPG with transparent background recommended). It will appear as the signee at the bottom of every letter.
                    </p>
                    @if($registrarSignature && file_exists(public_path($registrarSignature)))
                        <div class="text-center mb-3 p-3 border rounded bg-white">
                            <img src="{{ asset($registrarSignature) }}" alt="Signature" style="max-height: 80px;">
                            <div class="mt-2">
                                <small class="text-muted d-block">Current signature (uploaded)</small>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="deleteSignatureBtn">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    @elseif($registrarSignature && file_exists(public_path('storage/' . $registrarSignature)))
                        {{-- Legacy row from before the upload-target move — file lives at the
                             old storage/ path. Render via the symlink so the registrar can
                             still preview / remove it. --}}
                        <div class="text-center mb-3 p-3 border rounded bg-white">
                            <img src="{{ asset('storage/' . $registrarSignature) }}" alt="Signature" style="max-height: 80px;">
                            <div class="mt-2">
                                <small class="text-muted d-block">Current signature (legacy)</small>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="deleteSignatureBtn">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    @elseif(file_exists(public_path('uploads/signatures/registrar_signature.png')) || file_exists(public_path('uploads/signatures/registrar_signature.jpg')) || file_exists(public_path('uploads/signatures/registrar_signature.jpeg')) || file_exists(public_path('uploads/signatures/registrar_signature.svg')))
                        {{-- A "live" fixed file placed directly in public/uploads/signatures/
                             by the registrar — show it so they know their drop-in worked. --}}
                        @php
                            $liveSignatureUrl = null;
                            foreach (['png', 'jpg', 'jpeg', 'svg'] as $ext) {
                                if (file_exists(public_path('uploads/signatures/registrar_signature.' . $ext))) {
                                    $liveSignatureUrl = asset('uploads/signatures/registrar_signature.' . $ext);
                                    break;
                                }
                            }
                        @endphp
                        @if($liveSignatureUrl)
                            <div class="text-center mb-3 p-3 border rounded bg-white">
                                <img src="{{ $liveSignatureUrl }}" alt="Signature" style="max-height: 80px;">
                                <div class="mt-2">
                                    <small class="text-muted d-block">Current signature (live file in public/uploads/signatures/)</small>
                                </div>
                            </div>
                        @endif
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

    // Auto-save on blur for individual fields. The full Save-Letter-Settings
    // form still works for body + letterhead; this is the per-field
    // experience the registrar asked for ("registrar name should be save on
    // click", "Acceptance Fees if i add it should also be save on click").
    //
    // Each field's status indicator flips to "Saving…" → "✓ Saved" /
    // "⚠ Failed" so the user always sees whether the AJAX PATCH landed.
    // The browser does NOT reload — the field stays put, the page stays
    // put, no scroll jumps.
    var fieldUrl = "{{ route('registrar.admission.saveLetterField') }}";
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
    if (!csrfToken) {
        var csrfInput = document.querySelector('input[name="_token"]');
        csrfToken = csrfInput ? csrfInput.value : null;
    }

    function setStatus(el, state, msg) {
        if (!el) return;
        el.dataset.state = state;
        el.textContent = msg || (
            state === 'saving' ? 'Saving…' :
            state === 'saved'  ? '✓ Saved' :
            state === 'error'  ? '⚠ Failed — click to retry' :
                                  ''
        );
        el.className = 'save-status small text-' + (
            state === 'saving' ? 'muted' :
            state === 'saved'  ? 'success' :
            state === 'error'  ? 'danger' :
                                  'muted'
        );
    }

    function postField(payload, statusEl) {
        if (!csrfToken) {
            setStatus(statusEl, 'error', 'CSRF token missing — refresh the page');
            return Promise.resolve(false);
        }
        setStatus(statusEl, 'saving');
        return fetch(fieldUrl, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        })
        .then(function (r) {
            // Always parse JSON; if the server returns an HTML error page
            // (e.g. 419) we still want a useful message.
            return r.text().then(function (text) {
                var body = null;
                try { body = JSON.parse(text); } catch (e) { body = null; }
                return { ok: r.ok, status: r.status, body: body, raw: text };
            });
        })
        .then(function (out) {
            if (out.ok && out.body && out.body.ok) {
                setStatus(statusEl, 'saved');
                return true;
            }
            var msg = (out.body && out.body.error)
                ? out.body.error
                : ('Server returned ' + out.status);
            setStatus(statusEl, 'error', msg);
            return false;
        })
        .catch(function (e) {
            setStatus(statusEl, 'error', e.message || 'Network error');
            return false;
        });
    }

    // Registrar name — save on blur.
    var nameInput = document.getElementById('registrar_name');
    var nameStatus = document.getElementById('registrar_name_status');
    if (nameInput && nameStatus) {
        nameInput.addEventListener('blur', function () {
            postField({ field: 'registrar_name', value: nameInput.value }, nameStatus);
        });
    }

    // Fees — save the whole list on blur of any fee input. Sending the
    // whole list (rather than one row) keeps server logic identical to
    // the master Save button and avoids per-row race conditions.
    function collectFees() {
        if (!feesContainer) return [];
        var rows = feesContainer.querySelectorAll('.fee-row');
        var arr = [];
        rows.forEach(function (row) {
            var nameEl = row.querySelector('input[name*="[name]"]');
            var amtEl  = row.querySelector('input[name*="[amount]"]');
            if (!nameEl || !amtEl) return;
            arr.push({ name: nameEl.value, amount: amtEl.value });
        });
        return arr;
    }

    if (feesContainer) {
        // Use capture so we catch blur on dynamically-cloned rows too.
        feesContainer.addEventListener('blur', function (e) {
            if (!e.target.matches('input[name*="[name]"], input[name*="[amount]"]')) return;
            var row  = e.target.closest('.fee-row');
            var stat = row && row.querySelector('.fee-save-status');
            if (!stat) return;
            postField({ field: 'fees', value: collectFees() }, stat);
        }, true);

        // When the registrar clicks "Add Fee", the JS clones the first row
        // and clears the inputs — but the clone has the .fee-save-status
        // span copied from the source. We need to reset it to 'idle' so
        // the indicator starts empty for the new row.
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                setTimeout(function () {
                    var rows = feesContainer.querySelectorAll('.fee-row');
                    var last = rows[rows.length - 1];
                    if (last) {
                        var stat = last.querySelector('.fee-save-status');
                        if (stat) setStatus(stat, 'idle');
                    }
                }, 0);
            });
        }

        // Removing a row → save the surviving list (the remove
        // handler above runs first, then we re-collect and post).
        feesContainer.addEventListener('click', function (e) {
            if (!e.target.closest('.remove-fee')) return;
            setTimeout(function () {
                var stat = feesContainer.querySelector('.fee-save-status');
                if (stat) postField({ field: 'fees', value: collectFees() }, stat);
            }, 0);
        });
    }
});
</script>
@endpush