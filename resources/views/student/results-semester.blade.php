@extends('layouts.app')

@section('title', 'Semester Results')

@section('content')
<div class="page-header">
    <h4>Semester Results</h4>
</div>

@if(isset($semester))
<div class="card">
    <div class="card-header">
        <h5>{{ $semester->name ?? 'Current Semester' }} - {{ $semester->session ?? '' }}</h5>
    </div>
    <div class="card-body">
        @if(isset($results) && count($results) > 0)
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Credit Unit</th>
                        <th>CA Score</th>
                        <th>Exam Score</th>
                        <th>Total</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $result)
                        <tr>
                            <td>{{ $result->course->code ?? $result->course_code ?? 'N/A' }}</td>
                            <td>{{ $result->course->title ?? $result->course_title ?? 'N/A' }}</td>
                            <td>{{ $result->course->credit_unit ?? $result->credit_unit ?? 0 }}</td>
                            <td>{{ $result->ca_score ?? $result->test_score ?? '-' }}</td>
                            <td>{{ $result->exam_score ?? '-' }}</td>
                            <td>{{ $result->total ?? $result->score ?? '-' }}</td>
                            <td><strong>{{ $result->grade ?? '-' }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No results available for this semester.</p>
        @endif
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="fas fa-tools fa-3x mb-3"></i>
        <p>No semester selected.</p>
    </div>
</div>
@endif
@endsection
