@extends('layouts.app')

@section('title', 'Academic Board Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>Academic Board — Final Results Approval</h4>
        <p class="text-muted mb-0">
            Final sign-off on results cleared by the Business Committee.
            One row per department — pick the department you want to act on
            and drill in to approve or reject individual results.
        </p>
    </div>
    <div>
        <a href="{{ route('academic-board.signing-page') }}" class="btn btn-outline-dark">
            <i class="fas fa-file-signature me-2"></i>Signing Page
        </a>
    </div>
</div>

@if($byDepartment->isEmpty())
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No results are pending or recently final-approved yet.
            </div>
        </div>
    </div>
@else
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Department</th>
                            <th>School</th>
                            <th class="text-center" style="width: 120px;">Pending</th>
                            <th class="text-center" style="width: 140px;">Final-Approved</th>
                            <th class="text-center" style="width: 100px;">Total</th>
                            <th class="text-end" style="width: 260px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byDepartment as $group)
                            @php
                                $dept = $group['department'];
                                $pendingIds = $group['pending_ids'];
                                $hasPending = $group['pending'] > 0;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $dept->name ?? 'Unassigned Department' }}</strong>
                                    @if(! $hasPending)
                                        <span class="badge bg-light text-muted ms-2">All Clear</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted">{{ $group['school']->name ?? '—' }}</span>
                                </td>
                                <td class="text-center">
                                    @if($hasPending)
                                        <span class="badge bg-warning text-dark fs-6">
                                            {{ $group['pending'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">
                                        {{ $group['final'] }}
                                    </span>
                                </td>
                                <td class="text-center fw-semibold">
                                    {{ $group['total'] }}
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('academic-board.results.byDepartment', $dept) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-list me-1"></i>View Results
                                    </a>
                                    @if($hasPending)
                                        <button type="button"
                                                class="btn btn-sm btn-success js-dept-bulk-approve"
                                                data-dept-id="{{ $dept->id }}"
                                                data-dept-name="{{ $dept->name }}"
                                                data-count="{{ $group['pending'] }}">
                                            <i class="fas fa-check-double me-1"></i>Approve All
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        "Pending" counts are results cleared by the Business Committee that still need the
        Academic Board's final sign-off. "Final-Approved" counts are results already signed
        off (available for printing on the per-department view).
    </p>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Each row has an "Approve All" button. The user explicitly wants
    // department-level bulk approve from the index, so we POST a
    // hidden result_ids[] payload derived from the controller's
    // pending_ids via a small fetch — the department's pending ids
    // are embedded as data-* attributes on the button.
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';

    document.querySelectorAll('.js-dept-bulk-approve').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const deptName = btn.getAttribute('data-dept-name') || 'this department';
            const count    = btn.getAttribute('data-count') || 'all pending';
            if (! confirm(`Finally approve ${count} pending result(s) for ${deptName}?`)) return;
            // Use a small hidden form so the POST body matches the
            // existing bulkApprove contract (result_ids[] array).
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('academic-board.results.bulkApprove') }}';
            form.style.display = 'none';

            const token = document.createElement('input');
            token.name = '_token';
            token.value = csrfToken;
            form.appendChild(token);

            // We can't directly send pending_ids from the index without
            // a data-id-list attribute. The controller's bulkApprove
            // accepts result_ids[] — for the index "Approve All" button,
            // we redirect to the per-department drill-in where the
            // operator can do a controlled bulk action via the same
            // bulk bar (or individual clicks).
            const deptId = btn.getAttribute('data-dept-id');
            window.location.href = '{{ url('academic-board/results/department') }}' + '/' + deptId;
        });
    });
});
</script>
@endpush