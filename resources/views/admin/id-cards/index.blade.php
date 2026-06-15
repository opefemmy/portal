@extends('layouts.app')

@section('title', 'Student ID Cards')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Student ID Cards</h4>
    <form method="POST" action="{{ route('admin.id-cards.bulk') }}" class="d-flex gap-2">
        @csrf
        <select name="department_id" class="form-select" style="width: 200px;">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
            @endforeach
        </select>
        <select name="level" class="form-select" style="width: 150px;">
            <option value="">All Levels</option>
            <option value="1">ND1 (100L)</option>
            <option value="2">ND (200L)</option>
            <option value="3">HND1 (300L)</option>
            <option value="4">HND2 (400L)</option>
        </select>
        <button type="submit" class="btn btn-success">
            <i class="fas fa-print me-2"></i>Print All Selected
        </button>
    </form>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Search by matric number or name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="department_id">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.id-cards.print') }}">
            <div class="table-responsive">
                <table class="table datatable">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                            </th>
                            <th>Matric Number</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Programme</th>
                            <th>Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                        <tr>
                            <td>
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox">
                            </td>
                            <td>{{ $student->matric_number }}</td>
                            <td>{{ $student->user->name ?? 'N/A' }}</td>
                            <td>{{ $student->department->name ?? 'N/A' }}</td>
                            <td>{{ $student->programme->name ?? 'N/A' }}</td>
                            <td>{{ $student->level_display }}</td>
                            <td>
                                <a href="{{ route('admin.id-cards.generate', $student) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Generate ID Card">
                                    <i class="fas fa-id-card"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">No students found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-print me-2"></i>Print Selected
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function toggleAll(source) {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = source.checked);
}
</script>
@endpush
@endsection