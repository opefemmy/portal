@extends('layouts.app')

@section('title', 'My Results')

@section('content')
@php
$user = auth()->user();
$student = $user->student;

// Calculate totals
$totalCourses = $results->count();
$totalScore = $results->sum('total_score');
$totalGradePoints = 0;
$totalUnits = 0;

// Get classification based on GPA
$gpa = 0;
if ($results->count() > 0) {
    foreach ($results as $result) {
        $course = $result->studentCourse->course ?? null;
        if ($course) {
            $units = $course->units ?? 0;
            $gradePoint = $result->grade_point ?? 0;
            $totalGradePoints += $gradePoint * $units;
            $totalUnits += $units;
        }
    }
    $gpa = $totalUnits > 0 ? round($totalGradePoints / $totalUnits, 2) : 0;
}

// Get honours classification
$classification = '';
$classBadge = '';
if ($gpa >= 4.5) {
    $classification = 'FIRST CLASS HONOURS';
    $classBadge = 'bg-warning text-dark';
} elseif ($gpa >= 3.5) {
    $classification = 'SECOND CLASS UPPER';
    $classBadge = 'bg-success';
} elseif ($gpa >= 3.0) {
    $classification = 'SECOND CLASS UPPER';
    $classBadge = 'bg-success';
} elseif ($gpa >= 2.5) {
    $classification = 'SECOND CLASS LOWER';
    $classBadge = 'bg-info';
} elseif ($gpa >= 2.0) {
    $classification = 'THIRD CLASS';
    $classBadge = 'bg-secondary';
} elseif ($gpa >= 1.5) {
    $classification = 'PASS';
    $classBadge = 'bg-primary';
} else {
    $classification = 'FAIL';
    $classBadge = 'bg-danger';
}

// Grading scale reference
$gradingScale = [
    ['range' => '70-100', 'grade' => 'A', 'point' => '4.00', 'remark' => 'DISTINCTION'],
    ['range' => '60-69', 'grade' => 'B', 'point' => '3.50', 'remark' => 'UPPER CREDIT'],
    ['range' => '50-59', 'grade' => 'C', 'point' => '3.00', 'remark' => 'LOWER CREDIT'],
    ['range' => '45-49', 'grade' => 'D', 'point' => '2.50', 'remark' => 'PASS'],
    ['range' => '40-44', 'grade' => 'E', 'point' => '2.00', 'remark' => 'PASS'],
    ['range' => '0-39', 'grade' => 'F', 'point' => '0.00', 'remark' => 'FAIL'],
];
@endphp

<div class="page-header">
    <h4>My Results</h4>
</div>

{{-- Result Summary Card --}}
<div class="card mb-4 border-3" style="border-color: #1a237e;">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Academic Summary</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Total Courses</h6>
                        <h2 class="text-primary">{{ $totalCourses }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Total Score</h6>
                        <h2 class="text-success">{{ $totalScore }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Current GPA</h6>
                        <h2 class="text-info">{{ number_format($gpa, 2) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h6 class="text-muted">Classification</h6>
                        <h4><span class="badge {{ $classBadge }}">{{ $classification }}</span></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Grading Scale Reference --}}
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Grading System</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Score Range</th>
                        <th>Grade</th>
                        <th>Point</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gradingScale as $scale)
                    <tr>
                        <td>{{ $scale['range'] }}</td>
                        <td><strong>{{ $scale['grade'] }}</strong></td>
                        <td>{{ $scale['point'] }}</td>
                        <td>
                            <span class="badge bg-{{ $scale['remark'] == 'FAIL' ? 'danger' : ($scale['remark'] == 'PASS' ? 'warning text-dark' : 'success') }}">
                                {{ $scale['remark'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Course Results --}}
<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-book me-2"></i>Course Results ({{ $totalCourses }} Courses)</h5>
    </div>
    <div class="card-body">
        @if($results->count() > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">S/N</th>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th class="text-center">Units</th>
                        <th class="text-center">CA</th>
                        <th class="text-center">Exam</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Grade</th>
                        <th class="text-center">Point</th>
                        <th class="text-center">Remark</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $index => $result)
                    @php
                    $course = $result->studentCourse->course ?? null;
                    $units = $course->units ?? 0;
                    $gradePoint = $result->grade_point ?? 0;

                    // Determine remark based on grade point
                    $remark = '';
                    if ($gradePoint >= 4.0) {
                        $remark = 'DISTINCTION';
                        $remarkClass = 'success';
                    } elseif ($gradePoint >= 3.5) {
                        $remark = 'UPPER CREDIT';
                        $remarkClass = 'success';
                    } elseif ($gradePoint >= 3.0) {
                        $remark = 'LOWER CREDIT';
                        $remarkClass = 'info';
                    } elseif ($gradePoint >= 2.5) {
                        $remark = 'PASS';
                        $remarkClass = 'warning';
                    } elseif ($gradePoint >= 2.0) {
                        $remark = 'PASS';
                        $remarkClass = 'warning';
                    } else {
                        $remark = 'FAIL';
                        $remarkClass = 'danger';
                    }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td><strong>{{ $course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $course->title ?? $course->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $units }}</td>
                        <td class="text-center">{{ $result->ca ?? 0 }}</td>
                        <td class="text-center">{{ $result->exam ?? 0 }}</td>
                        <td class="text-center"><strong>{{ $result->total_score ?? 0 }}</strong></td>
                        <td class="text-center"><span class="badge bg-primary fs-6">{{ $result->grade ?? '-' }}</span></td>
                        <td class="text-center">{{ number_format($gradePoint, 1) }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $remarkClass }}">{{ $remark }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">TOTAL</th>
                        <th class="text-center">{{ $totalUnits }}</th>
                        <th colspan="4" class="text-end">GPA:</th>
                        <th class="text-center"><strong>{{ number_format($gpa, 2) }}</strong></th>
                        <th class="text-center"><span class="badge {{ $classBadge }}">{{ $classification }}</span></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
            <h5 class="text-muted">No Results Available</h5>
            <p>Your results will appear here once uploaded by your lecturers.</p>
        </div>
        @endif
    </div>
</div>
@endsection