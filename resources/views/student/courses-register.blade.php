@extends('layouts.app')

@section('title', 'Register Courses')

@section('content')
<div class="page-header">
    <h4>Course Registration</h4>
    <p class="text-muted">Select courses for {{ $student->level_display ?? 'Level ' . $student->level }}</p>
</div>

@php
    // Banner config. Matches SchoolFeeCalculator::canRegisterSemester() so
    // the messaging here is the source of truth.
    $fullyPaid = $canRegisterFirstSem && $canRegisterSecondSem;
@endphp
@if($fullyPaid)
    <div class="alert alert-success mb-4">
        <i class="fas fa-check-circle me-2"></i>
        <strong>100% school fees paid.</strong> Both first and second semester courses are open for registration.
    </div>
@elseif($canRegisterFirstSem)
    <div class="alert alert-warning mb-4">
        <i class="fas fa-exclamation-triangle me-2"></i>
        You have paid the <strong>60% first installment</strong>. First-semester courses are open;
        second-semester courses unlock once you pay the remaining 40%.
    </div>
@else
    <div class="alert alert-danger mb-4">
        <i class="fas fa-lock me-2"></i>
        School fees are unpaid. Course registration is locked.
    </div>
@endif

<form method="POST" action="{{ route('student.courses.register') }}">
    @csrf

    {{-- Carry Over Courses (Must Register) --}}
    @if($carryOverCourses->count() > 0)
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Carry Over Courses (Must Register)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-warning">
                        <tr>
                            <th width="50">Select</th>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($carryOverCourses as $carryOver)
                        <tr>
                            <td>
                                <input type="checkbox" name="courses[]" value="{{ $carryOver->course->id }}" checked class="form-check-input" disabled>
                                <input type="hidden" name="courses[]" value="{{ $carryOver->course->id }}">
                                <input type="hidden" name="course_types[{{ $carryOver->course->id }}]" value="carry_over">
                            </td>
                            <td>{{ $carryOver->course->code }}</td>
                            <td>{{ $carryOver->course->title }}</td>
                            <td>{{ $carryOver->course->units }}</td>
                            <td><span class="badge bg-warning text-dark">Carry Over</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Carry-over search — searches the student's past failed
         results (grade F / pass_status=fail / total<40) so the
         student can pick up a course they failed from a previous
         session even when no CarryOverCourse row exists yet.
         Matches by code prefix or title fragment. --}}
    <div class="card mb-4 border-secondary">
        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-search me-2"></i>Search My Carry-Over Courses</h5>
            <small>Type a course code (e.g. MTH 101) or a title fragment.</small>
        </div>
        <div class="card-body">
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-9">
                    <label class="form-label small mb-1">Course code or title</label>
                    <input type="text" id="carryover-search-input"
                           class="form-control"
                           placeholder="e.g. MTH 101 or Mathematics"
                           autocomplete="off">
                </div>
                <div class="col-md-3 d-grid">
                    <button type="button" id="carryover-search-btn" class="btn btn-outline-secondary">
                        <i class="fas fa-search me-1"></i>Search Past Failures
                    </button>
                </div>
            </div>
            <div id="carryover-search-results">
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Shows every course you previously registered for and either scored an <strong>F</strong> or recorded a fail pass-status. Tick to add it as a Carry-Over this session.
                </p>
            </div>
        </div>
    </div>

    {{-- Main Courses --}}
    @if($mainCourses->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Main Courses</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-2">Tick a course to register, then choose its type below (Main / Elective / Carry-Over).</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-primary">
                        <tr>
                            <th width="50">Select</th>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Semester</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mainCourses as $course)
                        @php $locked = !$fullyPaid && $course->semester === 'second'; @endphp
                        <tr class="{{ $locked ? 'table-secondary text-muted' : '' }}">
                            <td>
                                <input type="checkbox" name="courses[]" value="{{ $course->id }}"
                                       class="form-check-input course-checkbox"
                                       data-units="{{ $course->units }}"
                                       {{ $locked ? 'disabled' : '' }}>
                            </td>
                            <td>{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->units }}</td>
                            <td>
                                {{ ucfirst($course->semester) }}
                                @if($locked)
                                    <span class="badge bg-secondary">Locked — pay 40%</span>
                                @endif
                            </td>
                            <td>
                                <select name="course_types[{{ $course->id }}]"
                                        class="form-select form-select-sm course-type-select"
                                        {{ $locked ? 'disabled' : '' }}>
                                    <option value="main" selected>Main</option>
                                    <option value="elective">Elective</option>
                                    <option value="carry_over">Carry-Over</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Elective Courses (Choose One or More) --}}
    @if($electiveCourses->count() > 0)
    <div class="card mb-4 border-info">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-选修 me-2"></i>Elective Courses (Select as needed)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-2">Electives are pre-suggested, but you can reclassify any selected course as Main / Elective / Carry-Over at registration time.</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-info">
                        <tr>
                            <th width="50">Select</th>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Semester</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($electiveCourses as $course)
                        @php $locked = !$fullyPaid && $course->semester === 'second'; @endphp
                        <tr class="{{ $locked ? 'table-secondary text-muted' : '' }}">
                            <td>
                                <input type="checkbox" name="courses[]" value="{{ $course->id }}"
                                       class="form-check-input course-checkbox"
                                       data-units="{{ $course->units }}"
                                       {{ $locked ? 'disabled' : '' }}>
                            </td>
                            <td>{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->units }}</td>
                            <td>
                                {{ ucfirst($course->semester) }}
                                @if($locked)
                                    <span class="badge bg-secondary">Locked — pay 40%</span>
                                @endif
                            </td>
                            <td>
                                <select name="course_types[{{ $course->id }}]"
                                        class="form-select form-select-sm course-type-select"
                                        {{ $locked ? 'disabled' : '' }}>
                                    <option value="main">Main</option>
                                    <option value="elective" selected>Elective</option>
                                    <option value="carry_over">Carry-Over</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Already Registered Courses --}}
    @if($registeredCourses->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Already Registered Courses</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>Course Code</th>
                            <th>Title</th>
                            <th>Units</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($registeredCourses as $regCourse)
                        <tr>
                            <td>{{ $regCourse->course->code }}</td>
                            <td>{{ $regCourse->course->title }}</td>
                            <td>{{ $regCourse->course->units }}</td>
                            <td>
                                @if($regCourse->course_type === 'carry_over')
                                    <span class="badge bg-warning">Carry Over</span>
                                @elseif($regCourse->course_type === 'elective')
                                    <span class="badge bg-info">Elective</span>
                                @else
                                    <span class="badge bg-primary">Main</span>
                                @endif
                            </td>
                            <td><span class="badge bg-success">{{ ucfirst($regCourse->status) }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- No Courses Available --}}
    @if($mainCourses->count() == 0 && $electiveCourses->count() == 0 && $carryOverCourses->count() == 0)
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No courses available for registration at this time. Please contact your department.
    </div>
    @else
    <div class="mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Register Selected Courses
        </button>
        <a href="{{ route('student.courses.print') }}" class="btn btn-secondary btn-lg ms-2" target="_blank">
            <i class="fas fa-print me-2"></i>Print Registration Form
        </a>
    </div>
    @endif
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('carryover-search-input');
    const btn   = document.getElementById('carryover-search-btn');
    const out   = document.getElementById('carryover-search-results');
    if (!input || !btn || !out) return;

    // Cache CSRF token once for the GET request (Laravel doesn't
    // require it for GETs, but if we ever extend this to POST
    // we'd already be set).
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function search(term) {
        const url = new URL('{{ route('student.courses.carryover-search') }}', window.location.origin);
        if (term) url.searchParams.set('q', term);
        out.innerHTML = '<p class="text-muted small"><i class="fas fa-spinner fa-spin me-1"></i>Searching past results…</p>';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken } })
            .then(r => r.json())
            .then(data => renderResults(data.carry_overs || []))
            .catch(err => {
                out.innerHTML = '<p class="text-danger small"><i class="fas fa-exclamation-circle me-1"></i>Search failed: ' + (err.message || err) + '</p>';
            });
    }

    function renderResults(rows) {
        if (rows.length === 0) {
            out.innerHTML = '<p class="text-muted small mb-0"><i class="fas fa-check-circle me-1 text-success"></i>No past failed courses found' +
                (input.value ? ' for "' + input.value + '"' : '') + '. You\'re all clear.</p>';
            return;
        }
        let html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr>' +
            '<th width="50">Add</th><th>Code</th><th>Title</th><th>Units</th><th>Semester</th><th>Failed In</th><th>Last Grade</th><th>Department</th>' +
            '</tr></thead><tbody>';
        rows.forEach(r => {
            const id = r.id;
            html += '<tr>' +
                '<td>' +
                    '<input type="checkbox" name="courses[]" value="' + id + '" class="form-check-input carryover-pick" data-units="' + r.units + '">' +
                    '<input type="hidden" name="course_types[' + id + ']" value="carry_over" class="carryover-type">' +
                '</td>' +
                '<td><code>' + r.code + '</code></td>' +
                '<td>' + r.title + '</td>' +
                '<td>' + r.units + '</td>' +
                '<td>' + (r.semester ? r.semester.charAt(0).toUpperCase() + r.semester.slice(1) : '—') + '</td>' +
                '<td>' + (r.failed_session || '—') + '</td>' +
                '<td><span class="badge bg-danger">F (' + (r.last_total ?? '—') + ')</span></td>' +
                '<td>' + (r.department || '—') + '</td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        out.innerHTML = html;

        // Whenever a searched carry-over is checked, ensure the
        // course_type hidden input for that id is also enabled so
        // the submitter picks it up.
        out.querySelectorAll('.carryover-pick').forEach(cb => {
            cb.addEventListener('change', () => {
                const id = cb.value;
                const typeInput = document.querySelector('input[name="course_types[' + id + ']"]');
                if (typeInput) {
                    typeInput.disabled = !cb.checked;
                }
            });
        });
    }

    btn.addEventListener('click', () => search(input.value.trim()));
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); search(input.value.trim()); }
    });
    // Debounced live search after 250ms of typing — the result
    // list is short (<20 rows), so this is cheap.
    let t;
    input.addEventListener('input', () => {
        clearTimeout(t);
        t = setTimeout(() => search(input.value.trim()), 250);
    });

    // Auto-load the carry-over list once on mount so the student
    // sees their full debt without typing anything.
    search('');
});
</script>
@endpush