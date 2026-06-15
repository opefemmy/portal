@extends('layouts.app')

@section('title', 'My Results')

@section('content')
<div class="page-header">
    <h4>My Results</h4>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>CA</th>
                        <th>Test</th>
                        <th>Exam</th>
                        <th>Total</th>
                        <th>Grade</th>
                        <th>GP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $result)
                    <tr>
                        <td>{{ $result->studentCourse->course->code ?? 'N/A' }}</td>
                        <td>{{ $result->ca ?? '-' }}</td>
                        <td>{{ $result->test ?? '-' }}</td>
                        <td>{{ $result->exam ?? '-' }}</td>
                        <td>{{ $result->total_score ?? '-' }}</td>
                        <td>{{ $result->grade ?? '-' }}</td>
                        <td>{{ $result->grade_point ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No results available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection