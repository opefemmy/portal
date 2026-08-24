@extends('layouts.app')

@section('title', 'Course Students - ' . $course->code)

@section('content')
<div class="page-header">
    <div class="row">
        <div class="col-md-8">
            <h4>{{ $course->code }} - {{ $course->title }}</h4>
            <p class="text-muted mb-0">
                Department: {{ $course->department->name ?? 'N/A' }} |
                Level: {{ $course->level }} |
                Session: {{ $assignment->session->name ?? 'N/A' }}
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('lecturer.courses') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Courses
            </a>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="btn-group" role="group">
            <a href="{{ route('lecturer.courses.results', $course) }}" class="btn btn-success">
                <i class="fas fa-edit me-2"></i>Enter/Edit Results
            </a>
            <a href="{{ route('lecturer.courses.template', $course) }}" class="btn btn-dark">
                <i class="fas fa-download me-2"></i>Download Excel Template
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Registered Students ({{ $studentCourses->count() }})</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Matric Number</th>
                        <th>Full Name</th>
                        <th>Programme</th>
                        <th>Level</th>
                        <th>Course Type</th>
                        <th>Result Status</th>
                        <th>Score</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentCourses as $sc)
                    @php
                        $result = $results[$sc->id] ?? null;
                    @endphp
                    <tr>
                        <td><strong>{{ $sc->student->matric_number ?? 'N/A' }}</strong></td>
                        <td>{{ $sc->student->user->name ?? 'N/A' }}</td>
                        <td>{{ $sc->student->programme->name ?? 'N/A' }}</td>
                        <td>{{ $sc->student->level ?? 'N/A' }}</td>
                        <td>
                            @if($sc->course_type === 'carry_over')
                                <span class="badge bg-warning">Carry Over</span>
                            @elseif($sc->course_type === 'elective')
                                <span class="badge bg-info">Elective</span>
                            @else
                                <span class="badge bg-primary">Main</span>
                            @endif
                        </td>
                        <td>
                            @if($result)
                                @php
                                    // Slice D — surface every pipeline stage
                                    // so the lecturer can see WHERE their
                                    // re-upload will land. The badge text
                                    // must mirror the approval chain
                                    // (HOD → Dean → BC → Academic Board).
                                    $badge = match($result->status) {
                                        'pending_approval'   => ['warning', 'Pending HOD'],
                                        'approved'           => ['info',    'Pending Dean'],
                                        'approved_by_dean'   => ['primary', 'Pending Business Committee'],
                                        'approved_by_business' => ['primary', 'Pending Academic Board'],
                                        'approved_final'     => ['success', 'Final Approved'],
                                        'rejected'           => ['danger',  'Rejected (HOD/Dean)'],
                                        'rejected_by_business' => ['danger', 'Rejected (BC)'],
                                        'rejected_final'     => ['danger',  'Rejected (Academic Board)'],
                                        'released', 'locked', 'withdrawn' => ['dark',   ucfirst($result->status)],
                                        default              => ['secondary', $result->status],
                                    };
                                @endphp
                                <span class="badge bg-{{ $badge[0] }}">{{ $badge[1] }}</span>
                            @else
                                <span class="badge bg-danger">Not Entered</span>
                            @endif
                        </td>
                        <td>{{ $result->total_score ?? '-' }}</td>
                        <td>
                            @if($result)
                                <strong>{{ $result->grade }}</strong>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{-- Slice D — show Upload / Re-upload at every
                                 non-terminal stage. The previous gate
                                 `status !== 'approved'` locked the icon
                                 once HOD signed off, so the lecturer
                                 couldn't correct a row that BC or the
                                 Academic Board had already sent past
                                 approval. We now hide the icon only
                                 when the row is finally approved,
                                 rejected-final, or admin-locked. The
                                 Lecturer\ResultController::update()
                                 method resets status to 'pending_approval'
                                 on save, so re-uploading re-enters the
                                 pipeline cleanly. --}}
                            @php
                                $finalStatuses = ['approved_final', 'rejected_final', 'released', 'locked', 'withdrawn'];
                            @endphp
                            @if($result && !in_array($result->status, $finalStatuses, true))
                                <a href="{{ route('lecturer.result.edit', $result) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="Upload / Re-upload result">
                                    <i class="fas fa-upload"></i>
                                </a>
                            @else
                                <span class="text-muted" title="Locked — final approval">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">No students registered for this course.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection