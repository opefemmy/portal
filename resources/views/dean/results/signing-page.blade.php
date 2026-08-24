@extends('layouts.app')

@section('title', 'Dean Signing Page')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>Dean Signing Page</h4>
        <p class="text-muted mb-0">Results you have personally approved at the Dean stage. Print or export the page with the signature block below.</p>
    </div>
    <div>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-2"></i>Print
        </button>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('dean.results.signing-page') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['department_id'] ?? null) == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Programme</label>
                <select name="programme_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($programmes as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['programme_id'] ?? null) == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($sessions as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['session_id'] ?? null) == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter me-1"></i>Apply Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            {{ $results->total() }} result{{ $results->total() === 1 ? '' : 's' }} approved at Dean stage
        </h5>
    </div>
    <div class="card-body">
        @if($results->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Department</th>
                            <th>Student</th>
                            <th>Matric No</th>
                            <th>Programme</th>
                            <th>Session</th>
                            <th>Total</th>
                            <th>Grade</th>
                            <th>Approved On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $result)
                            <tr>
                                <td>
                                    {{ $result->course->code ?? '—' }}<br>
                                    <small class="text-muted">{{ $result->course->title ?? '' }}</small>
                                </td>
                                <td>{{ $result->course->department->name ?? '—' }}</td>
                                <td>{{ $result->studentCourse->student->user->name ?? '—' }}</td>
                                <td><code>{{ $result->studentCourse->student->matric_number ?? '—' }}</code></td>
                                <td>{{ $result->studentCourse->student->programme->name ?? '—' }}</td>
                                <td>{{ $result->studentCourse->session->name ?? '—' }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-{{ $result->grade == 'A' ? 'success' : ($result->grade == 'F' ? 'danger' : 'warning') }}">
                                        {{ $result->grade ?? '—' }}
                                    </span>
                                </td>
                                <td>{{ $result->approved_at ? $result->approved_at->format('d/m/Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $results->appends(request()->query())->links() }}
            </div>

            @include('admin.transcripts._signing_block')
        @else
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No results match the current filter.
            </div>
        @endif
    </div>
</div>
@endsection