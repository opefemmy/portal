@extends('layouts.app')

@section('title', 'Grades')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Grading System</h4>
    <a href="{{ route('admin.grades.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Grade
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Score Range</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                        <th>Remark</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                    <tr>
                        <td>{{ $grade->min_score }} - {{ $grade->max_score }}</td>
                        <td>{{ $grade->grade }}</td>
                        <td>{{ $grade->grade_point }}</td>
                        <td>{{ $grade->remark }}</td>
                        <td>
                            <a href="{{ route('admin.grades.edit', $grade) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">No grades configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection