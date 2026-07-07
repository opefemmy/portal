@extends('layouts.app')

@section('title', 'OnCourses - Course Assignments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>OnCourses - Course Assignments</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignCourseModal">
        <i class="fas fa-plus me-2"></i>Assign Course to Lecturer
    </button>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Course Code</th>
                        <th>Course Title</th>
                        <th>Department</th>
                        <th>Lecturer</th>
                        <th>Session</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                    <tr>
                        <td><strong>{{ $assignment->course->code ?? 'N/A' }}</strong></td>
                        <td>{{ $assignment->course->title ?? 'N/A' }}</td>
                        <td>{{ $assignment->course->department->name ?? 'N/A' }}</td>
                        <td>
                            @if($assignment->lecturer)
                                <span class="badge bg-success">
                                    <i class="fas fa-user-check me-1"></i>{{ $assignment->lecturer->name }}
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-user-times me-1"></i>Not Assigned
                                </span>
                            @endif
                        </td>
                        <td>{{ $assignment->session->name ?? 'N/A' }}</td>
                        <td>{{ ucfirst($assignment->semester) }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#assignModal{{ $assignment->id }}"
                                    title="Assign Lecturer">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.course-assignments.destroy', $assignment) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove Assignment" onclick="return confirm('Remove this course assignment?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Assign Lecturer Modal for each row -->
                    <div class="modal fade" id="assignModal{{ $assignment->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Assign Lecturer - {{ $assignment->course->code ?? '' }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.course-assignments.update', $assignment->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Search Lecturer</label>
                                            <input type="text" class="form-control lecturer-search-input"
                                                   placeholder="Search by name or staff ID..."
                                                   data-assignment-id="{{ $assignment->id }}">
                                            <div class="lecturer-search-results mt-2" id="results{{ $assignment->id }}"></div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="lecturer_id{{ $assignment->id }}" class="form-label">Selected Lecturer</label>
                                            <select name="lecturer_id" id="lecturer_id{{ $assignment->id }}" class="form-select" required>
                                                <option value="">Select Lecturer</option>
                                                @if($assignment->lecturer)
                                                    <option value="{{ $assignment->lecturer->id }}" selected>{{ $assignment->lecturer->name }}</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="semester{{ $assignment->id }}" class="form-label">Semester</label>
                                            <select name="semester" id="semester{{ $assignment->id }}" class="form-select">
                                                <option value="first" {{ $assignment->semester == 'first' ? 'selected' : '' }}>First</option>
                                                <option value="second" {{ $assignment->semester == 'second' ? 'selected' : '' }}>Second</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Assign Lecturer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No course assignments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Main Assign Course Modal -->
<div class="modal fade" id="assignCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign New Course to Lecturer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('admin.course-assignments.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Select Course</label>
                                <select name="course_id" id="course_id" class="form-select" required>
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}">{{ $course->code }} - {{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="session_id" class="form-label">Session</label>
                                <select name="session_id" id="session_id" class="form-select" required>
                                    @foreach($sessions as $session)
                                        <option value="{{ $session->id }}" {{ $session->is_current ? 'selected' : '' }}>{{ $session->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <select name="semester" id="semester" class="form-select" required>
                                    <option value="first">First Semester</option>
                                    <option value="second">Second Semester</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="lecturer_id_new" class="form-label">Search Lecturer</label>
                                <input type="text" class="form-control" id="lecturer_search_new" placeholder="Search by name or staff ID...">
                                <div id="lecturer_results_new" class="mt-2"></div>
                                <select name="lecturer_id" id="lecturer_id_new" class="form-select mt-2" required>
                                    <option value="">Select Lecturer</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Search lecturer function
    function searchLecturer(query, resultsContainer, selectId) {
        if (query.length < 2) {
            $('#' + resultsContainer).html('');
            return;
        }

        $.ajax({
            url: '{{ route("admin.users.search") }}',
            type: 'GET',
            data: { search: query, role: 'lecturer' },
            success: function(data) {
                let html = '<ul class="list-group">';
                if (data.length > 0) {
                    data.forEach(function(user) {
                        html += `<li class="list-group-item list-group-item-action" style="cursor:pointer;"
                                    onclick="selectLecturer('${user.id}', '${user.name}', '${resultsContainer}', '${selectId}')">
                                    ${user.name} <small class="text-muted">(${user.staff_id || 'No ID'})</small>
                                 </li>`;
                    });
                } else {
                    html += '<li class="list-group-item">No lecturer found</li>';
                }
                html += '</ul>';
                $('#' + resultsContainer).html(html);
            }
        });
    }

    // For main modal
    $('#lecturer_search_new').on('keyup', function() {
        searchLecturer($(this).val(), 'lecturer_results_new', 'lecturer_id_new');
    });

    // For row modals
    $('.lecturer-search-input').on('keyup', function() {
        const assignmentId = $(this).data('assignment-id');
        searchLecturer($(this).val(), 'results' + assignmentId, 'lecturer_id' + assignmentId);
    });
});

// Global function to select lecturer
function selectLecturer(userId, userName, resultsContainer, selectId) {
    $('#' + selectId).html(`<option value="${userId}" selected>${userName}</option>`);
    $('#' + resultsContainer).html('');
    $('#lecturer_search_new').val(userName);
}
</script>
@endpush

@endsection