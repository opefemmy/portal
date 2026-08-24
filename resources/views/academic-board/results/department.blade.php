@extends('layouts.app')

@section('title', 'Academic Board — ' . ($department->name ?? 'Department'))

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>
            <a href="{{ route('academic-board.results') }}" class="text-decoration-none text-muted">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
            {{ $department->name ?? 'Unassigned Department' }}
        </h4>
        <p class="text-muted mb-0">
            School: {{ $school->name ?? '—' }}
            · {{ $rows->count() }} result(s) —
            {{-- pending count uses the same definition as the index
                 (status=approved_by_business). --}}
            <strong class="text-warning">{{ $pendingIds ? count($pendingIds) : 0 }}</strong>
            pending final approval,
            <strong class="text-success">{{ $rows->count() - ($pendingIds ? count($pendingIds) : 0) }}</strong>
            already final-approved.
        </p>
    </div>
    <div>
        <a href="{{ route('academic-board.signing-page') }}" class="btn btn-outline-dark">
            <i class="fas fa-file-signature me-2"></i>Signing Page
        </a>
    </div>
</div>

@if($rows->isEmpty())
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No results are pending or recently final-approved for this department.
            </div>
        </div>
    </div>
@else
    {{-- Bulk-action bar lives OUTSIDE every section's per-row form.
         Each row carries a checkbox; bulk approve/reject POSTs via
         fetch() to the existing bulk endpoints. (Wrapping the rows
         in a single <form> would flatten nested PUT forms → 405 on
         bulk-approve; same root cause as the prior card layout.) --}}
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 bg-light border rounded">
        <span class="me-2"><strong><span id="ab-selected-count">0</span></strong> selected</span>
        <button type="button" id="ab-bulk-approve" class="btn btn-success btn-sm" disabled>
            <i class="fas fa-check-double me-1"></i> Bulk Final Approve
        </button>
        <button type="button" id="ab-bulk-reject" class="btn btn-danger btn-sm" disabled>
            <i class="fas fa-times-circle me-1"></i> Bulk Reject
        </button>
        <input type="text" name="remarks" id="ab-bulk-remarks" class="form-control form-control-sm ms-auto" style="max-width: 320px" placeholder="Optional remarks (required for bulk reject)">
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="40">
                                {{-- Per-table select-all only marks
                                     pending rows. --}}
                                <input type="checkbox" class="ab-group-select-all">
                            </th>
                            <th>Status</th>
                            <th>Course</th>
                            <th>Student</th>
                            <th>Matric No</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th width="240">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $result)
                            @php $isFinal = $result->status === 'approved_final'; @endphp
                            <tr>
                                <td>
                                    {{-- Bulk approve / reject only act
                                         on pending rows. Approved rows
                                         stay read-only. --}}
                                    @if(! $isFinal)
                                        <input type="checkbox" class="ab-row-check" value="{{ $result->id }}">
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $isFinal ? 'success' : 'warning text-dark' }}">
                                        {{ $isFinal ? 'Final-Approved' : 'Pending Final' }}
                                    </span>
                                </td>
                                <td>
                                    {{ $result->studentCourse->course->code ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $result->studentCourse->course->title ?? '' }}</small>
                                </td>
                                <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-{{ $result->grade == 'A' ? 'success' : ($result->grade == 'F' ? 'danger' : 'warning') }}">
                                        {{ $result->grade ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    @if(! $isFinal)
                                        <form method="POST" action="{{ route('academic-board.results.approve', $result) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this result?')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('academic-board.results.reject', $result) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this result?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('academic-board.results.print', $result) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           target="_blank"
                                           title="Open this result's signed sheet in a new tab">
                                            <i class="fas fa-print me-1"></i>Print Sheet
                                        </a>
                                        @if($result->studentCourse->student)
                                            <a href="{{ route('academic-board.results.print-student', $result->studentCourse->student) }}"
                                               class="btn btn-sm btn-outline-dark"
                                               target="_blank"
                                               title="Open the student's full transcript in a new tab">
                                                <i class="fas fa-file-alt me-1"></i>Transcript
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rowChecks = document.querySelectorAll('.ab-row-check');
    const countEl   = document.getElementById('ab-selected-count');
    const approveBtn = document.getElementById('ab-bulk-approve');
    const rejectBtn  = document.getElementById('ab-bulk-reject');
    const remarksEl  = document.getElementById('ab-bulk-remarks');
    const groupSelectAlls = document.querySelectorAll('.ab-group-select-all');

    // Per-table select-all toggles pending rows only — the row
    // checkboxes already exclude approved rows.
    groupSelectAlls.forEach(function (master) {
        master.addEventListener('change', function () {
            const table = master.closest('table');
            if (!table) return;
            table.querySelectorAll('.ab-row-check').forEach(function (c) {
                c.checked = master.checked;
            });
            refresh();
        });
    });

    if (!countEl || rowChecks.length === 0) {
        // No pending rows — leave the bulk bar visible but disabled.
        if (approveBtn) approveBtn.disabled = true;
        if (rejectBtn) rejectBtn.disabled = true;
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function refresh() {
        const ids = Array.from(rowChecks).filter(c => c.checked).map(c => c.value);
        countEl.textContent = ids.length;
        approveBtn.disabled = ids.length === 0;
        rejectBtn.disabled = ids.length === 0;
    }

    rowChecks.forEach(c => c.addEventListener('change', refresh));

    function bulkSubmit(url, remarks) {
        const ids = Array.from(rowChecks).filter(c => c.checked).map(c => c.value);
        if (ids.length === 0) return;
        const body = new URLSearchParams();
        body.set('_token', csrfToken);
        ids.forEach(id => body.append('result_ids[]', id));
        if (remarks !== undefined) body.set('remarks', remarks);
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
        }).then(resp => {
            if (resp.redirected) {
                window.location.href = resp.url;
            } else {
                window.location.reload();
            }
        }).catch(err => {
            alert('Bulk action failed: ' + err.message);
            window.location.reload();
        });
    }

    approveBtn.addEventListener('click', function() {
        if (!confirm('Finally approve ' + countEl.textContent + ' selected result(s)?')) return;
        bulkSubmit('{{ route('academic-board.results.bulkApprove') }}');
    });

    rejectBtn.addEventListener('click', function() {
        if (!remarksEl.value.trim()) {
            alert('Please enter a remark before bulk rejecting.');
            remarksEl.focus();
            return;
        }
        if (!confirm('Reject ' + countEl.textContent + ' selected result(s)?')) return;
        bulkSubmit('{{ route('academic-board.results.bulkReject') }}', remarksEl.value.trim());
    });
});
</script>
@endpush