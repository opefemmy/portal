@extends('layouts.app')

@section('title', 'Academic Board Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>Academic Board — Final Results Approval</h4>
        <p class="text-muted mb-0">Final sign-off on results cleared by the Business Committee.</p>
    </div>
    <div>
        <a href="{{ route('academic-board.signing-page') }}" class="btn btn-outline-dark">
            <i class="fas fa-file-signature me-2"></i>Signing Page
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pending Final Approval</h5>
    </div>
    <div class="card-body">
        @if($results->count() > 0)
            {{-- Bulk-action bar lives outside any wrapping <form>.
                 Per-row approve/reject forms (PUT) sit inside the
                 table, and wrapping them with a bulk POST form would
                 flatten them in browsers → 405 on bulk-approve. The
                 bulk buttons POST via fetch() so we don't need a
                 wrapping form. --}}
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

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="ab-select-all"></th>
                            <th>Course</th>
                            <th>Level</th>
                            <th>Student</th>
                            <th>Matric No</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                        <tr>
                            <td><input type="checkbox" class="ab-row-check" value="{{ $result->id }}"></td>
                            <td>
                                {{ $result->studentCourse->course->code ?? 'N/A' }}<br>
                                <small class="text-muted">{{ $result->studentCourse->course->title ?? '' }}</small>
                            </td>
                            <td>
                                @if($result->studentCourse->course)
                                    {{ \App\Models\Course::getLevelName($result->studentCourse->course->level) }}
                                @else
                                    N/A
                                @endif
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
                                <form method="POST" action="{{ route('academic-board.results.approve', $result) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this result?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('academic-board.results.reject', $result) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this result?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No results pending final approval.
            </div>
        @endif
    </div>
</div>

{{-- Recently final-approved results — printable archive.
     Each row has a Print Sheet button that opens the per-result
     signed sheet, plus a Transcript button that opens the
     per-student printable transcript. --}}
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Recently Final-Approved Results</h5>
        <small class="text-muted">Click Print to open the signed sheet.</small>
    </div>
    <div class="card-body">
        @if(isset($finalApproved) && $finalApproved->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Student</th>
                            <th>Matric No</th>
                            <th>School</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Approved On</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($finalApproved as $result)
                            <tr>
                                <td>
                                    {{ $result->studentCourse->course->code ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $result->studentCourse->course->title ?? '' }}</small>
                                </td>
                                <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->school->name ?? 'N/A' }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-{{ $result->grade == 'A' ? 'success' : ($result->grade == 'F' ? 'danger' : 'warning') }}">
                                        {{ $result->grade ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $result->approved_at ? $result->approved_at->format('d/m/Y') : 'N/A' }}</td>
                                <td>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No final-approved results yet. Once you approve a batch, this list will populate.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('ab-select-all');
    const rowChecks = document.querySelectorAll('.ab-row-check');
    const countEl = document.getElementById('ab-selected-count');
    const approveBtn = document.getElementById('ab-bulk-approve');
    const rejectBtn = document.getElementById('ab-bulk-reject');
    const remarksEl = document.getElementById('ab-bulk-remarks');
    if (!selectAll || rowChecks.length === 0) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    function refresh() {
        const ids = Array.from(rowChecks).filter(c => c.checked).map(c => c.value);
        countEl.textContent = ids.length;
        approveBtn.disabled = ids.length === 0;
        rejectBtn.disabled = ids.length === 0;
    }

    selectAll.addEventListener('change', function() {
        rowChecks.forEach(c => c.checked = this.checked);
        refresh();
    });
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