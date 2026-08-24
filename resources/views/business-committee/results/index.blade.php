@extends('layouts.app')

@section('title', 'Business Committee Results')

@section('content')
<div class="page-header">
    <h4>Business Committee — Results Review</h4>
    <p class="text-muted mb-0">Results forwarded by the Dean's office, awaiting Business Committee sign-off.</p>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pending Results</h5>
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
                <span class="me-2"><strong><span id="bc-selected-count">0</span></strong> selected</span>
                <button type="button" id="bc-bulk-approve" class="btn btn-success btn-sm" disabled>
                    <i class="fas fa-check-double me-1"></i> Bulk Approve
                </button>
                <button type="button" id="bc-bulk-reject" class="btn btn-danger btn-sm" disabled>
                    <i class="fas fa-times-circle me-1"></i> Bulk Reject
                </button>
                <input type="text" name="remarks" id="bc-bulk-remarks" class="form-control form-control-sm ms-auto" style="max-width: 320px" placeholder="Optional remarks (required for bulk reject)">
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="bc-select-all"></th>
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
                            <td><input type="checkbox" class="bc-row-check" value="{{ $result->id }}"></td>
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
                                <form method="POST" action="{{ route('business-committee.results.approve', $result) }}" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this result?')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('business-committee.results.reject', $result) }}" class="d-inline">
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
                No pending results forwarded by the Dean's office at this time.
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('bc-select-all');
    const rowChecks = document.querySelectorAll('.bc-row-check');
    const countEl = document.getElementById('bc-selected-count');
    const approveBtn = document.getElementById('bc-bulk-approve');
    const rejectBtn = document.getElementById('bc-bulk-reject');
    const remarksEl = document.getElementById('bc-bulk-remarks');
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
        if (!confirm('Approve ' + countEl.textContent + ' selected result(s) at Business Committee stage?')) return;
        bulkSubmit('{{ route('business-committee.results.bulkApprove') }}');
    });

    rejectBtn.addEventListener('click', function() {
        if (!remarksEl.value.trim()) {
            alert('Please enter a remark before bulk rejecting.');
            remarksEl.focus();
            return;
        }
        if (!confirm('Reject ' + countEl.textContent + ' selected result(s)?')) return;
        bulkSubmit('{{ route('business-committee.results.bulkReject') }}', remarksEl.value.trim());
    });
});
</script>
@endpush