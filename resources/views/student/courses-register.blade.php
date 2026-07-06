@extends('layouts.app')

@section('title', 'Register Courses')

@section('content')
<div class="page-header">
    <h4>Course Registration</h4>
    <p class="text-muted">Select courses for {{ $student->level_display ?? 'Level ' . $student->level }}</p>
</div>

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

    {{-- Main Courses --}}
    @if($mainCourses->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-book me-2"></i>Main Courses</h5>
        </div>
        <div class="card-body">
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
                        <tr>
                            <td>
                                <input type="checkbox" name="courses[]" value="{{ $course->id }}" class="form-check-input course-checkbox" data-units="{{ $course->units }}">
                                <input type="hidden" name="course_types[{{ $course->id }}]" value="main">
                            </td>
                            <td>{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->units }}</td>
                            <td>{{ ucfirst($course->semester) }}</td>
                            <td><span class="badge bg-primary">Main</span></td>
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
            <h5 class="mb-0"><i class="fas fa选修 me-2"></i>Elective Courses (Select as needed)</h5>
        </div>
        <div class="card-body">
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
                        <tr>
                            <td>
                                <input type="checkbox" name="courses[]" value="{{ $course->id }}" class="form-check-input course-checkbox" data-units="{{ $course->units }}">
                                <input type="hidden" name="course_types[{{ $course->id }}]" value="elective">
                            </td>
                            <td>{{ $course->code }}</td>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->units }}</td>
                            <td>{{ ucfirst($course->semester) }}</td>
                            <td><span class="badge bg-info">Elective</span></td>
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