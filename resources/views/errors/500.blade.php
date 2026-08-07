@extends('layouts.app')

@section('title', 'Something went wrong')

@section('content')
<div class="page-header">
    <h4>Something went wrong</h4>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>We could not load this page
                </h5>
            </div>
            <div class="card-body">
                <p class="lead">
                    A server-side error prevented this page from loading. The full
                    detail has been logged so the technical team can investigate.
                </p>

                @auth
                <p>
                    You can go back to the dashboard and try again. If the problem
                    keeps happening, please contact the admissions office with the
                    time and what you clicked just before the error appeared.
                </p>

                @if(auth()->user()->hasRole('applicant'))
                    <a href="{{ url('/applicant/dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Applicant Dashboard
                    </a>
                @elseif(auth()->user()->hasRole('student'))
                    <a href="{{ url('/student/dashboard') }}" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Student Dashboard
                    </a>
                @else
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                @endif
                @else
                <p>
                    Please <a href="{{ url('/login') }}">sign in</a> again or go back
                    to the home page.
                </p>
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Home
                </a>
                @endauth

                @if(config('app.debug') && isset($exception))
                <hr>
                <h6 class="text-muted">Debug details</h6>
                <p class="small">
                    <strong>Class:</strong> {{ get_class($exception) }}<br>
                    <strong>Message:</strong> {{ $exception->getMessage() }}<br>
                    <strong>File:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}
                </p>
                <details>
                    <summary>Stack trace</summary>
                    <pre class="small bg-light p-2">{{ $exception->getTraceAsString() }}</pre>
                </details>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection