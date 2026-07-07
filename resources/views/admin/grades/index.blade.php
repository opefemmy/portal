@extends('layouts.app')

@section('title', 'Grades')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Grading System</h4>
    <a href="{{ route('admin.grades.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Grade
    </a>
</div>

<!-- Grade Classifications Guide -->
<div class="card mb-4 border-info">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Honours Classification Guide</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-crown text-warning me-2"></i><strong>First Class</strong></span>
                        <span class="badge bg-primary rounded-pill">GPA 4.5 - 5.0</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-medal text-success me-2"></i><strong>Second Class Upper</strong></span>
                        <span class="badge bg-primary rounded-pill">GPA 3.5 - 4.49</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-medal text-info me-2"></i><strong>Second Class Lower</strong></span>
                        <span class="badge bg-primary rounded-pill">GPA 2.5 - 3.49</span>
                    </li>
                </ul>
            </div>
            <div class="col-md-4">
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-award text-warning me-2"></i><strong>Third Class</strong></span>
                        <span class="badge bg-primary rounded-pill">GPA 1.5 - 2.49</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-check text-success me-2"></i><strong>Pass</strong></span>
                        <span class="badge bg-primary rounded-pill">GPA 1.0 - 1.49</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-times text-danger me-2"></i><strong>Fail</strong></span>
                        <span class="badge bg-primary rounded-pill">GPA 0 - 0.99</span>
                    </li>
                </ul>
            </div>
            <div class="col-md-4">
                <div class="alert alert-info mb-0">
                    <h6><i class="fas fa-info-circle me-2"></i>How Classification Works</h6>
                    <p class="mb-0 small">The honours classification is determined by the Cumulative Grade Point Average (CGPA) at the end of the programme. Students must pass all courses to graduate.</p>
                </div>
            </div>
        </div>
    </div>
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
                        <th>Weight</th>
                        <th>Remark</th>
                        <th>Classification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grades as $grade)
                    <tr>
                        <td><strong>{{ $grade->min_score }} - {{ $grade->max_score }}</strong></td>
                        <td><span class="badge bg-primary">{{ $grade->grade }}</span></td>
                        <td>{{ $grade->grade_point }}</td>
                        <td>{{ $grade->gpa_weight }}</td>
                        <td>{{ $grade->remark }}</td>
                        <td>
                            @switch($grade->classification)
                                @case('first_class')
                                    <span class="badge bg-warning text-dark">First Class</span>
                                    @break
                                @case('second_class_upper')
                                    <span class="badge bg-success">Second Class (Upper)</span>
                                    @break
                                @case('second_class_lower')
                                    <span class="badge bg-info">Second Class (Lower)</span>
                                    @break
                                @case('third_class')
                                    <span class="badge bg-secondary">Third Class</span>
                                    @break
                                @case('pass')
                                    <span class="badge bg-primary">Pass</span>
                                    @break
                                @case('fail')
                                    <span class="badge bg-danger">Fail</span>
                                    @break
                                @default
                                    <span class="badge bg-secondary">-</span>
                            @endswitch
                        </td>
                        <td>
                            <a href="{{ route('admin.grades.edit', $grade) }}" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No grades configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection