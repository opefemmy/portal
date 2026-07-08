@extends('layouts.app')

@section('title', 'My Timetable')

@section('content')
<div class="page-header">
    <h4>My Timetable</h4>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Venue</th>
                        <th>Lecturer</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($timetables as $timetable)
                    <tr>
                        <td>{{ ucfirst($timetable->day) }}</td>
                        <td>{{ $timetable->start_time }} - {{ $timetable->end_time }}</td>
                        <td>{{ $timetable->course->code ?? 'N/A' }}</td>
                        <td>{{ $timetable->venue }}</td>
                        <td>{{ $timetable->lecturer->name ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No timetable available.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection