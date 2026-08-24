@extends('layouts.app')

@section('title', 'Dean Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>School Results Management</h4>
        <p class="text-muted mb-0">Results forwarded by HODs in your school, awaiting Dean sign-off.</p>
    </div>
    <div>
        <a href="{{ route('dean.results.signing-page') }}" class="btn btn-outline-dark">
            <i class="fas fa-file-signature me-2"></i>Signing Page
        </a>
    </div>
</div>

@php
$user = auth()->user();
$schoolId = $user->school_id;
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pending Results for Approval</h5>
        <small class="text-muted">Results forwarded by HODs in your school, awaiting Dean sign-off.</small>
    </div>
    <div class="card-body">
        @if($schoolId)
            @if($results->count() > 0)
                {{-- Bulk-action bar lives outside any wrapping <form>.
                     Per-row approve/reject forms (PUT) sit inside the
                     table, and wrapping them with a bulk POST form
                     would flatten them in browsers → 405 on bulk-approve.
                     The bulk buttons POST via fetch() so we don't need
                     a wrapping form. --}}
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 bg-light border rounded">
                    <span class="me-2"><strong><span id="dean-selected-count">0</span></strong> selected</span>
                    <button type="button" id="dean-bulk-approve" class="btn btn-success btn-sm" disabled>
                        <i class="fas fa-check-double me-1"></i> Bulk Approve
                    </button>
                    <button type="button" id="dean-bulk-reject" class="btn btn-danger btn-sm" disabled>
                        <i class="fas fa-times-circle me-1"></i> Bulk Reject
                    </button>
                    <input type="text" name="remarks" id="dean-bulk-remarks" class="form-control form-control-sm ms-auto" style="max-width: 320px" placeholder="Optional remarks (required for bulk reject)">
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="dean-select-all"></th>
                                <th>Department</th>
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
                                <td><input type="checkbox" class="dean-row-check" value="{{ $result->id }}"></td>
                                <td>{{ $result->course->department->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $result->course->code ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $result->course->title ?? '' }}</small>
                                </td>
                                <td>
                                    @if($result->course)
                                        {{ \App\Models\Course::getLevelName($result->course->level) }}
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
                                    <form method="POST" action="{{ route('dean.results.approve', $result) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this result?')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('dean.results.reject', $result) }}" class="d-inline">
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
                    No pending results for approval in your school.
                </div>
            @endif
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You are not assigned to any school. Please contact the administrator.
            </div>
        @endif
    </div>
</div>

<!-- Recently Approved (Dean stage) Results -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Recently Approved at Dean Stage</h5>
    </div>
    <div class="card-body">
        @if($schoolId)
            @if($approvedResults->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Course</th>
                                <th>Level</th>
                                <th>Student</th>
                                <th>Matric No</th>
                                <th>Total</th>
                                <th>Grade</th>
                                <th>Approved By</th>
                                <th>Approved At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($approvedResults as $result)
                            <tr>
                                <td>{{ $result->course->department->name ?? 'N/A' }}</td>
                                <td>{{ $result->course->code ?? 'N/A' }}</td>
                                <td>
                                    @if($result->course)
                                        {{ \App\Models\Course::getLevelName($result->course->level) }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-success">{{ $result->grade ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $result->approvedBy->name ?? 'N/A' }}</td>
                                <td>{{ $result->approved_at ? $result->approved_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No Dean-approved results yet.</p>
            @endif
        @else
            <p class="text-muted">No school assigned.</p>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('dean-select-all');
    const rowChecks = document.querySelectorAll('.dean-row-check');
    const countEl = document.getElementById('dean-selected-count');
    const approveBtn = document.getElementById('dean-bulk-approve');
    const rejectBtn = document.getElementById('dean-bulk-reject');
    const remarksEl = document.getElementById('dean-bulk-remarks');
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
        if (!confirm('Approve ' + countEl.textContent + ' selected result(s) at Dean stage?')) return;
        bulkSubmit('{{ route('dean.results.bulkApprove') }}');
    });

    rejectBtn.addEventListener('click', function() {
        if (!remarksEl.value.trim()) {
            alert('Please enter a remark before bulk rejecting.');
            remarksEl.focus();
            return;
        }
        if (!confirm('Reject ' + countEl.textContent + ' selected result(s)?')) return;
        bulkSubmit('{{ route('dean.results.bulkReject') }}', remarksEl.value.trim());
    });
});
</script>
@endpush