@extends('layouts.app')

@section('title', 'Grades')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Grading System</h4>
    <div>
        <a href="{{ route('admin.grades.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Grade
        </a>
    </div>
</div>

<!-- Success Message -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Grade Classifications Guide (Editable) -->
<div class="card mb-4 border-info">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i>Honours Classification Guide</h5>
        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addClassificationModal">
            <i class="fas fa-plus"></i> Add New
        </button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Classification</th>
                            <th>GPA Range</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classifications as $classification)
                        <tr>
                            <td>
                                <strong>{{ $classification->name }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ number_format($classification->min_gpa, 2) }} - {{ number_format($classification->max_gpa, 2) }}</span>
                            </td>
                            <td>{{ $classification->description }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editClassificationModal{{ $classification->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.grades.classification.destroy', $classification->id) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Classification Modal -->
                        <div class="modal fade" id="editClassificationModal{{ $classification->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit {{ $classification->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="{{ route('admin.grades.classification.update', $classification->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Min GPA</label>
                                                <input type="number" step="0.01" name="min_gpa" class="form-control" value="{{ $classification->min_gpa }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Max GPA</label>
                                                <input type="number" step="0.01" name="max_gpa" class="form-control" value="{{ $classification->max_gpa }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control">{{ $classification->description }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr><td colspan="4" class="text-center">No classifications defined</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <div class="alert alert-info mb-0">
                    <h6><i class="fas fa-info-circle me-2"></i>How Classification Works</h6>
                    <p class="mb-0 small">The honours classification is determined by the Cumulative Grade Point Average (CGPA) at the end of the programme. Students must pass all courses to graduate.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grading System (Editable) -->
<div class="card mb-4 border-success">
    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Grading System</h5>
        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addScaleModal">
            <i class="fas fa-plus"></i> Add New
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Min Score</th>
                        <th>Max Score</th>
                        <th>Grade</th>
                        <th>Point</th>
                        <th>Description</th>
                        <th>Classification</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gradingScales as $scale)
                    <tr>
                        <td>{{ $scale->min_score }}</td>
                        <td>{{ $scale->max_score }}</td>
                        <td><span class="badge bg-primary">{{ $scale->grade }}</span></td>
                        <td>{{ number_format($scale->grade_point, 2) }}</td>
                        <td>{{ $scale->remark }}</td>
                        <td>{{ $scale->classification }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editScaleModal{{ $scale->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.grades.scale.destroy', $scale->id) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Scale Modal -->
                    <div class="modal fade" id="editScaleModal{{ $scale->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Grade {{ $scale->grade }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.grades.scale.update', $scale->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Min Score</label>
                                                    <input type="number" name="min_score" class="form-control" value="{{ $scale->min_score }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Max Score</label>
                                                    <input type="number" name="max_score" class="form-control" value="{{ $scale->max_score }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Grade Point</label>
                                                    <input type="number" step="0.01" name="grade_point" class="form-control" value="{{ $scale->grade_point }}" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">GPA Weight</label>
                                                    <input type="number" step="0.01" name="gpa_weight" class="form-control" value="{{ $scale->gpa_weight }}" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Remark</label>
                                            <input type="text" name="remark" class="form-control" value="{{ $scale->remark }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Classification</label>
                                            <select name="classification" class="form-select">
                                                <option value="">Select Classification</option>
                                                @foreach($classifications as $class)
                                                <option value="{{ $class->slug }}" {{ $scale->classification == $class->slug ? 'selected' : '' }}>{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr><td colspan="7" class="text-center">No grading scales defined</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Legacy Grades Table (if any) -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Legacy Grade Configuration</h5>
    </div>
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
                        <td colspan="7" class="text-center py-4">No legacy grades configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Classification Modal -->
<div class="modal fade" id="addClassificationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Classification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.grades.classification.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" placeholder="e.g., first_class" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Min GPA</label>
                                <input type="number" step="0.01" name="min_gpa" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Max GPA</label>
                                <input type="number" step="0.01" name="max_gpa" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Scale Modal -->
<div class="modal fade" id="addScaleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Grading Scale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.grades.scale.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Grade</label>
                                <input type="text" name="grade" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Min Score</label>
                                <input type="number" name="min_score" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Max Score</label>
                                <input type="number" name="max_score" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Grade Point</label>
                                <input type="number" step="0.01" name="grade_point" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">GPA Weight</label>
                                <input type="number" step="0.01" name="gpa_weight" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Classification</label>
                                <select name="classification" class="form-select">
                                    <option value="">Select Classification</option>
                                    @foreach($classifications as $class)
                                    <option value="{{ $class->slug }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remark</label>
                        <input type="text" name="remark" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection