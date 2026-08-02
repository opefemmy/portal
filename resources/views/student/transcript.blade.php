@extends('layouts.app')

@section('title', 'Transcript')

@section('content')
<div class="page-header">
    <h4>Academic Transcript</h4>
</div>

@if(isset($student))
<div class="card">
    <div class="card-header">
        <h5>Student Information</h5>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <th width="200">Name</th>
                <td>{{ $student->full_name ?? $student->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Matric Number</th>
                <td>{{ $student->matric_no ?? $student->matric_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Programme</th>
                <td>{{ $student->programme->name ?? $student->programme_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Department</th>
                <td>{{ $student->department->name ?? $student->department_name ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h5>Course Results</h5>
    </div>
    <div class="card-body">
        @if(isset($courses) && count($courses) > 0)
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Credit Unit</th>
                        <th>Score</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        @php
                            $result = isset($results) ? $results->where('course_id', $course->id)->first() : null;
                        @endphp
                        <tr>
                            <td>{{ $course->code ?? 'N/A' }}</td>
                            <td>{{ $course->title ?? $course->name ?? 'N/A' }}</td>
                            <td>{{ $course->credit_unit ?? $course->credit ?? 0 }}</td>
                            <td>{{ $result->score ?? $result->total ?? '-' }}</td>
                            <td>{{ $result->grade ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No course results available.</p>
        @endif
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <p>No student data available.</p>
    </div>
</div>
@endif
@endsection
