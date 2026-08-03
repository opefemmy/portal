@extends('layouts.app')

@section('title', 'Academic Board Results')

@section('content')
<div class="page-header">
    <h4>Academic Board — Final Results Approval</h4>
    <p class="text-muted mb-0">Final sign-off on results cleared by the Business Committee.</p>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pending Final Approval</h5>
    </div>
    <div class="card-body">
        @if($results->count() > 0)
            <form id="ab-bulk-form" method="POST" action="">
                @csrf
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2 bg-light border rounded">
                    <span class="me-2"><strong><span id="ab-selected-count">0</span></strong> selected</span>
                    <button type="button" id="ab-bulk-approve" class="btn btn-success btn-sm" disabled>
                        <i class="fas fa-check-double me-1"></i> Bulk Final Approve
                    </button>
                    <button type="button" id="ab-bulk-reject" class="btn btn-danger btn-sm" disabled>
                        <i class="fas fa-times-circle me-1"></i> Bulk Reject
                    </button>
                    <input type="hidden" name="result_ids" id="ab-result-ids" value="">
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
            </form>
        @else
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No results pending final approval.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ab-bulk-form');
    if (!form) return;
    const selectAll = document.getElementById('ab-select-all');
    const rowChecks = document.querySelectorAll('.ab-row-check');
    const countEl = document.getElementById('ab-selected-count');
    const idsEl = document.getElementById('ab-result-ids');
    const approveBtn = document.getElementById('ab-bulk-approve');
    const rejectBtn = document.getElementById('ab-bulk-reject');
    const remarksEl = document.getElementById('ab-bulk-remarks');

    function refresh() {
        const ids = Array.from(rowChecks).filter(c => c.checked).map(c => c.value);
        countEl.textContent = ids.length;
        idsEl.value = ids.join(',');
        approveBtn.disabled = ids.length === 0;
        rejectBtn.disabled = ids.length === 0;
    }

    selectAll.addEventListener('change', function() {
        rowChecks.forEach(c => c.checked = this.checked);
        refresh();
    });
    rowChecks.forEach(c => c.addEventListener('change', refresh));

    approveBtn.addEventListener('click', function() {
        if (!confirm('Finally approve ' + countEl.textContent + ' selected result(s)?')) return;
        form.action = '{{ route('academic-board.results.bulkApprove') }}';
        form.submit();
    });

    rejectBtn.addEventListener('click', function() {
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