@extends('layouts.app')

@section('title', 'Transcripts')

@section('content')
<div class="page-header">
    <h4>Student Transcripts</h4>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" placeholder="Search by matric number or name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Matric Number</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Programme</th>
                    <th>Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                <tr>
                    <td>{{ $student->matric_number }}</td>
                    <td>{{ $student->user->name ?? 'N/A' }}</td>
                    <td>{{ $student->department->name ?? 'N/A' }}</td>
                    <td>{{ $student->programme->name ?? 'N/A' }}</td>
                    <td>{{ $student->level_display }}</td>
                    <td>
                        <a href="{{ route('admin.transcripts.show', $student) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View Transcript">
                            <i class="fas fa-file-alt"></i>
                        </a>
                        <a href="{{ route('admin.transcripts.print', $student) }}" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Print Transcript" target="_blank">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No students found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{ $students->links() }}
    </div>
</div>
@endsection