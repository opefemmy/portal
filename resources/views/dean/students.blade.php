@extends('layouts.app')

@section('title', 'Students')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4>Students in My School</h4>
        <p class="text-muted mb-0">Filtered by your assigned school. Filter further by department, programme and level.</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('dean.students') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Name, email, or matric no.">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All departments</option>
                    @foreach($departments as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['department_id'] ?? null) == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Programme</label>
                <select name="programme_id" class="form-select form-select-sm" {{ ($programmes ?? collect())->isEmpty() ? 'disabled' : '' }}>
                    <option value="">All programmes</option>
                    @foreach($programmes as $id => $name)
                        <option value="{{ $id }}" {{ ($filters['programme_id'] ?? null) == $id ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Level</label>
                <select name="level" class="form-select form-select-sm">
                    <option value="">All levels</option>
                    @foreach([1 => '100L / ND1', 2 => '200L / ND2', 3 => '300L / HND1', 4 => '400L / HND2'] as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['level'] ?? null) == $val ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            {{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }} found
        </h5>
    </div>
    <div class="card-body">
        @if($students->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Matric No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Programme</th>
                            <th>Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td><code>{{ $student->matric_number }}</code></td>
                                <td>{{ $student->user->name ?? '—' }}</td>
                                <td>{{ $student->user->email ?? '—' }}</td>
                                <td>{{ $student->department->name ?? '—' }}</td>
                                <td>{{ $student->programme->name ?? '—' }}</td>
                                <td>{{ $student->level_display ?? $student->level }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $students->appends(request()->query())->links() }}
            </div>
        @else
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No students matched your filter. Try clearing one or more filters above.
            </div>
        @endif
    </div>
</div>
@endsection