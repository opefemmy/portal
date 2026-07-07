@extends('layouts.app')

@section('title', 'Results Management')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Results Management</h4>
        <p class="text-muted mb-0">View and manage student results</p>
    </div>
    <div>
        <a href="{{ route('admin.results.upload') }}" class="btn btn-primary">
            <i class="fas fa-upload me-2"></i>Upload Results
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.results.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Session</label>
                <select name="session_id" class="form-select">
                    <option value="">All Sessions</option>
                    @foreach(\App\Models\Session::all() as $session)
                        <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>
                            {{ $session->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Semester</label>
                <select name="semester" class="form-select">
                    <option value="">All Semesters</option>
                    <option value="first" {{ request('semester') == 'first' ? 'selected' : '' }}>First</option>
                    <option value="second" {{ request('semester') == 'second' ? 'selected' : '' }}>Second</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>CA</th>
                        <th>Test</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $result)
                    <tr>
                        <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                        <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $result->studentCourse->course->code ?? 'N/A' }}</td>
                        <td>{{ $result->ca ?? 0 }}</td>
                        <td>{{ $result->test ?? 0 }}</td>
                        <td>{{ $result->exam ?? 0 }}</td>
                        <td><strong>{{ $result->total_score ?? 0 }}</strong></td>
                        <td>
                            <span class="badge bg-{{ $result->grade === 'A' ? 'success' : ($result->grade === 'F' ? 'danger' : 'warning') }}">
                                {{ $result->grade ?? 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $result->status === 'approved' ? 'success' : ($result->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($result->status) }}
                            </span>
                        </td>
                        <td>
                            @if($result->status === 'pending')
                            <form method="POST" action="{{ route('admin.results.approve', $result) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.results.reject', $result) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted">No results found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $results->links() }}
    </div>
</div>
@endsection