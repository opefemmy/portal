{{--
    Academic Board — combined signing page.

    Renders every result that has cleared the Business Committee
    (status = approved_by_business), grouped by school / department /
    programme via the filter form, and follows the table with the
    combined HOD · Dean · BC · AB · Registrar · Rector signing block.

    The filter shape is `?school_id=&department_id=&programme_id=&session_id=`
    so the same URL is shareable between approval rounds. Bulk approve
    scopes to the same filters — the controller already accepts a
    `result_ids[]` payload and re-applies the school scope at write time.

    Slice E of the multi-area portal plan.
--}}
@extends('layouts.app')

@section('title', 'Signing Page — Academic Board')

@section('head')
<style>
    /* Print orientation: A4 landscape. The signing grid spans the full
       page width — landscape gives all six signature cells enough
       horizontal room to sit truly beside each other. */
    @page { size: A4 landscape; margin: 10mm; }
    @media print {
        html, body { background: #fff !important; }
        .no-print, .main-header, .main-footer, .navbar, .sidebar, .breadcrumb,
        .page-header .btn, form .d-flex.gap-2 { display: none !important; }
        .card { border: 1px solid #aaa !important; box-shadow: none !important; }
        .signing-block { page-break-inside: avoid; margin-top: 8px !important; }
        table { font-size: 9pt; }
        .table th, .table td { padding: 3px 5px !important; }
    }
</style>
@endsection

@section('content')
<div class="page-header d-flex justify-content-between align-items-center no-print">
    <div>
        <h4>Signing Page — Final Results</h4>
        <p class="text-muted mb-0">Printable record of every result cleared by the Business Committee and awaiting final sign-off.</p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print / Save as PDF
        </button>
    </div>
</div>

{{-- Filter form — school / department / programme / session. Hidden
     in print. The form posts back to the same URL so the controller
     can apply the same scopes to the bulk-approve endpoint. --}}
<div class="card mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('academic-board.signing-page') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">School</label>
                <select name="school_id" class="form-select form-select-sm">
                    <option value="">All Schools</option>
                    @foreach($schools ?? [] as $school)
                        <option value="{{ $school->id }}" @selected(($filters['school_id'] ?? null) == $school->id)>
                            {{ $school->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}" @selected(($filters['department_id'] ?? null) == $dept->id)>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Programme</label>
                <select name="programme_id" class="form-select form-select-sm">
                    <option value="">All Programmes</option>
                    @foreach($programmes ?? [] as $prog)
                        <option value="{{ $prog->id }}" @selected(($filters['programme_id'] ?? null) == $prog->id)>
                            {{ $prog->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions ?? [] as $sess)
                        <option value="{{ $sess->id }}" @selected(($filters['session_id'] ?? null) == $sess->id)>
                            {{ $sess->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-filter me-1"></i>Apply
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Results table — same shape as academic-board.results.index so the
     user can bulk-final-approve from this view too. The bulk form
     action defaults to academic-board.results.bulkApprove; the JS at
     the bottom sets it to bulkReject when the reject button is clicked. --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Pending Final Approval ({{ $results->count() }})</h5>
        @if($results->count() > 0)
            <span class="text-muted small no-print">
                <i class="fas fa-info-circle me-1"></i>
                Tick rows to bulk-approve, then sign below.
            </span>
        @endif
    </div>
    <div class="card-body">
        @if($results->count() > 0)
            <form id="ab-signing-form" method="POST" action="{{ route('academic-board.results.bulkApprove') }}">
                @csrf
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 bg-light border rounded no-print">
                    <span class="me-2"><strong><span id="ab-signing-count">0</span></strong> selected</span>
                    <button type="submit" id="ab-signing-approve" class="btn btn-success btn-sm" disabled>
                        <i class="fas fa-check-double me-1"></i> Bulk Final Approve
                    </button>
                    <button type="button" id="ab-signing-reject" class="btn btn-danger btn-sm" disabled>
                        <i class="fas fa-times-circle me-1"></i> Bulk Reject
                    </button>
                    <input type="text" name="remarks" id="ab-signing-remarks" class="form-control form-control-sm ms-auto" style="max-width: 320px" placeholder="Optional remarks (required for bulk reject)">
                </div>

                {{-- Canonical institution header — same partial every
                     other printout uses. Hidden behind no-print so the
                     form chrome doesn't show up when printing, but the
                     letterhead DOES print. --}}
                <div class="d-none d-print-block mb-3">
                    @include('partials.print.institution-header')
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="40" class="no-print"><input type="checkbox" id="ab-signing-select-all"></th>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Matric No</th>
                                <th>Programme</th>
                                <th>Total</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                            <tr>
                                <td class="no-print"><input type="checkbox" class="ab-signing-row-check" value="{{ $result->id }}"></td>
                                <td>
                                    {{ $result->studentCourse->course->code ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $result->studentCourse->course->title ?? '' }}</small>
                                </td>
                                <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->programme->name ?? 'N/A' }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-{{ $result->grade == 'A' ? 'success' : ($result->grade == 'F' ? 'danger' : 'warning') }}">
                                        {{ $result->grade ?? 'N/A' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No results pending final approval for the selected filter.
            </div>
        @endif
    </div>
</div>

{{-- Combined signing block — included on every printable result sheet
     so HOD, Dean, BC, AB, Registrar and Rector all have their place. --}}
@include('admin.transcripts._signing_block')

<div class="text-center text-muted mt-4" style="font-size: 9pt;">
    Generated on {{ now()->format('d M Y, h:i A') }} ·
    {{ \App\Models\SystemSetting::getInstitutionName() }}
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ab-signing-form');
    if (!form) return;
    const selectAll = document.getElementById('ab-signing-select-all');
    const rowChecks = document.querySelectorAll('.ab-signing-row-check');
    const countEl = document.getElementById('ab-signing-count');
    const approveBtn = document.getElementById('ab-signing-approve');
    const rejectBtn = document.getElementById('ab-signing-reject');
    const remarksEl = document.getElementById('ab-signing-remarks');

    function refresh() {
        const ids = Array.from(rowChecks).filter(c => c.checked).map(c => c.value);
        countEl.textContent = ids.length;
        approveBtn.disabled = ids.length === 0;
        rejectBtn.disabled = ids.length === 0;
    }

    selectAll?.addEventListener('change', function() {
        rowChecks.forEach(c => c.checked = this.checked);
        refresh();
    });
    rowChecks.forEach(c => c.addEventListener('change', refresh));

    rejectBtn?.addEventListener('click', function() {
        if (!remarksEl.value.trim()) {
            alert('Please enter a remark before bulk rejecting.');
            remarksEl.focus();
            return;
        }
        if (!confirm('Reject ' + countEl.textContent + ' selected result(s)?')) return;
        form.action = '{{ route('academic-board.results.bulkReject') }}';
        form.submit();
    });
});
</script>
@endpush
