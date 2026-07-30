@extends('layouts.app')

@section('title', 'Dean Results')

@section('content')
<div class="page-header">
    <h4>School Results Management</h4>
</div>

@php
$user = auth()->user();
$schoolId = $user->school_id;
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Pending Results for Approval</h5>
    </div>
    <div class="card-body">
        @if($schoolId)
            @php
            // Get all departments in this school
            $departmentIds = \App\Models\Department::where('school_id', $schoolId)->pluck('id');

            // Get all courses in these departments
            $courseIds = \App\Models\Course::whereIn('department_id', $departmentIds)->pluck('id');

            // Get results pending approval for these courses
            $results = \App\Models\Result::whereIn('course_id', $courseIds)
                ->where('status', 'pending_approval')
                ->with(['course', 'course.department', 'studentCourse.student.user', 'approvedBy'])
                ->latest()
                ->get();
            @endphp

            @if($results->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Matric No</th>
                                <th>CA1</th>
                                <th>CA2</th>
                                <th>Exam</th>
                                <th>Total</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $result)
                            <tr>
                                <td>{{ $result->course->department->name ?? 'N/A' }}</td>
                                <td>
                                    {{ $result->course->code ?? 'N/A' }}<br>
                                    <small class="text-muted">{{ $result->course->title ?? '' }}</small>
                                </td>
                                <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $result->ca1 ?? 0 }}</td>
                                <td>{{ $result->ca2 ?? 0 }}</td>
                                <td>{{ $result->exam ?? 0 }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-{{ $result->grade == 'A' ? 'success' : ($result->grade == 'F' ? 'danger' : 'warning') }}">
                                        {{ $result->grade ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('dean.results.approve', $result) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this result?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No pending results for approval in your school.
                </div>
            @endif
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                You are not assigned to any school. Please contact the administrator.
            </div>
        @endif
    </div>
</div>

<!-- Approved Results -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Recently Approved Results</h5>
    </div>
    <div class="card-body">
        @if($schoolId)
            @php
            $approvedResults = \App\Models\Result::whereIn('course_id', $courseIds)
                ->where('status', 'approved')
                ->with(['course', 'course.department', 'studentCourse.student.user', 'approvedBy'])
                ->latest()
                ->limit(20)
                ->get();
            @endphp

            @if($approvedResults->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Course</th>
                                <th>Student</th>
                                <th>Matric No</th>
                                <th>Total</th>
                                <th>Grade</th>
                                <th>Approved By</th>
                                <th>Approved At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($approvedResults as $result)
                            <tr>
                                <td>{{ $result->course->department->name ?? 'N/A' }}</td>
                                <td>{{ $result->course->code ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                                <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                                <td>{{ $result->total_score ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-success">{{ $result->grade ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $result->approvedBy->name ?? 'N/A' }}</td>
                                <td>{{ $result->approved_at ? $result->approved_at->format('d/m/Y h:i A') : 'N/A' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No approved results yet.</p>
            @endif
        @else
            <p class="text-muted">No school assigned.</p>
        @endif
    </div>
</div>
@endsection
