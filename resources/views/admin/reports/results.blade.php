@extends('layouts.app')

@section('title', 'Results Report')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Results Report</h4>
    <a href="{{ route('admin.reports') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Reports
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Student Name</th>
                        <th>Course</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Grade</th>
                        <th>Grade Point</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $result)
                    <tr>
                        <td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }}</td>
                        <td>{{ $result->studentCourse->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $result->studentCourse->course->code ?? 'N/A' }} - {{ $result->studentCourse->course->title ?? '' }}</td>
                        <td>{{ $result->studentCourse->session->name ?? 'N/A' }}</td>
                        <td>{{ $result->studentCourse->semester }}</td>
                        <td>{{ $result->grade->letter ?? 'N/A' }}</td>
                        <td>{{ $result->grade_point }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No results found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection