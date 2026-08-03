@extends('layouts.app')

@section('title', 'Previous Results')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Previous Results (Historical)</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.previous-results.template') }}" class="btn btn-outline-secondary">
            <i class="fas fa-download me-1"></i>Template
        </a>
        <a href="{{ route('admin.previous-results.create') }}" class="btn btn-primary">
            <i class="fas fa-upload me-1"></i>Upload
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Filter by student</label>
                <select name="student_id" class="form-select form-select-sm">
                    <option value="">All students</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->matric_number }} — {{ optional($s->user)->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Filter by session</label>
                <input type="text" name="session_name" value="{{ request('session_name') }}"
                       class="form-control form-control-sm" placeholder="e.g. 2021/2022">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
                <a href="{{ route('admin.previous-results.index') }}" class="btn btn-sm btn-outline-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Matric</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Session / Sem / Level</th>
                        <th class="text-end">Score</th>
                        <th>Grade</th>
                        <th>Source</th>
                        <th>Uploaded</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $i => $r)
                        <tr>
                            <td>{{ $results->firstItem() + $i }}</td>
                            <td><code>{{ optional($r->student)->matric_number ?? '—' }}</code></td>
                            <td>{{ optional($r->student?->user)->name ?? '—' }}</td>
                            <td>
                                <strong>{{ $r->course_code }}</strong>
                                @if($r->course_title)
                                    <br><small class="text-muted">{{ $r->course_title }}</small>
                                @endif
                                @if($r->units)
                                    <span class="badge bg-secondary">{{ $r->units }} units</span>
                                @endif
                            </td>
                            <td>
                                {{ $r->session_name }}<br>
                                <small class="text-muted">{{ ucfirst($r->semester) }} · Level {{ $r->level ?? '—' }}</small>
                            </td>
                            <td class="text-end fw-bold">{{ number_format($r->total_score, 1) }}</td>
                            <td>
                                <span class="badge bg-{{ $r->grade === 'F' ? 'danger' : 'success' }}">
                                    {{ $r->grade ?? '—' }}
                                </span>
                                @if($r->grade_point !== null)
                                    <small class="text-muted">({{ $r->grade_point }})</small>
                                @endif
                            </td>
                            <td><small>{{ $r->source_institution ?? '—' }}</small></td>
                            <td>
                                <small>{{ optional($r->uploader)->name ?? '—' }}</small><br>
                                <small class="text-muted">{{ optional($r->uploaded_at)->format('d M Y') }}</small>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.previous-results.destroy', $r) }}"
                                      onsubmit="return confirm('Delete this row?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="Delete this previous-result row">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No previous-results uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $results->links() }}
        </div>
    </div>
</div>
@endsection